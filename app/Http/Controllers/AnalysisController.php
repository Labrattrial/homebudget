<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Budget;
use Illuminate\Support\Facades\Cache;

class AnalysisController extends Controller
{
    public function index()
    {
        // Default to last 30 days
        $endDate = now();
        $startDate = now()->subDays(30);
        
        // Get transactions for the period
        $transactions = $this->getTransactions($startDate, $endDate);
        
        // Calculate metrics
        $metrics = $this->calculateMetrics($transactions, $startDate, $endDate);
        $categoryBreakdown = $this->getCategoryBreakdown($startDate, $endDate);
        $trendData = $this->getTrendData($startDate, $endDate);
        
        // Fetch the budget amount for the current month
        $currentMonth = now()->format('Y-m'); // Format: YYYY-MM
        $budget = Budget::where('user_id', Auth::id())
                        ->where('month', $currentMonth)
                        ->first();

        $budgetAmount = $budget ? $budget->amount_limit : 0; // Default to 0 if no budget is set

        return view('pages.analysis', [
            'totalSpending' => $metrics['totalSpending'],
            'dailyAverage' => $metrics['dailyAverage'],
            'categoryBreakdown' => $categoryBreakdown,
            'trendDates' => array_keys($trendData),
            'trendAmounts' => array_values($trendData),
            'categoryNames' => collect($categoryBreakdown)->pluck('name')->toArray(),
            'categoryAmounts' => collect($categoryBreakdown)->pluck('amount')->toArray(),
            'budgetAmount' => $budgetAmount, // Pass the budget amount to the view
        ]);
    }

    public function getDataByType($type)
    {
        try {
            $data = [];
            
            if ($type === 'weekly') {
                $data = $this->getWeeklyData(request());
            } elseif ($type === 'monthly') {
                $data = $this->getMonthlyData(request());
            }
            
            // Always include category breakdown
            $data['categoryNames'] = [];
            $data['categoryAmounts'] = [];
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function customDateRangeData(Request $request)
    {
        try {
            $startDate = Carbon::parse($request->input('start'));
            $endDate = Carbon::parse($request->input('end'));
            $viewType = $request->input('view', 'monthly');

            // Validate date range (max 1 year)
            if ($startDate->diffInDays($endDate) > 365) {
                return response()->json(['error' => 'Date range cannot exceed 1 year'], 400);
            }

            // Create cache key based on parameters
            $cacheKey = "analysis_data_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}_{$viewType}_" . Auth::id();

            // Return cached data if available
            return response()->json(Cache::remember($cacheKey, now()->addMinutes(15), function () use ($startDate, $endDate, $viewType) {
                $transactions = $this->getTransactions($startDate, $endDate);
                $metrics = $this->calculateMetrics($transactions, $startDate, $endDate);
                $categoryBreakdown = $this->getCategoryBreakdown($startDate, $endDate);
                $trendData = $this->getTrendData($startDate, $endDate);

                return [
                    'totalSpending' => $metrics['totalSpending'],
                    'dailyAverage' => $metrics['dailyAverage'],
                    'categoryNames' => collect($categoryBreakdown)->pluck('name')->toArray(),
                    'categoryAmounts' => collect($categoryBreakdown)->pluck('amount')->toArray(),
                    'trendDates' => array_keys($trendData),
                    'trendAmounts' => array_values($trendData),
                ];
            }));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load data', 'message' => $e->getMessage()], 500);
        }
    }

    protected function getTransactions($startDate, $endDate)
    {
        return Transaction::whereBetween('date', [$startDate, $endDate])
            ->where('user_id', Auth::id())
            ->select('id', 'date', 'amount', 'category_id') // Only fetch required columns
            ->with(['category:id,name']) // Eager load the category with specific columns
            ->get();
    }

    protected function calculateMetrics($transactions, $startDate, $endDate)
    {
        $totalSpending = $transactions->sum('amount');
        $days = $startDate->diffInDays($endDate) ?: 1;
        $dailyAverage = $totalSpending / $days;
        
        return [
            'totalSpending' => $totalSpending,
            'dailyAverage' => $dailyAverage,
        ];
    }

    protected function getCategoryBreakdown($startDate, $endDate)
    {
        $totalSpending = Transaction::whereBetween('date', [$startDate , $endDate])
            ->where('user_id', Auth::id())
            ->sum('amount');

        return Category::join('transactions', 'categories.id', '=', 'transactions.category_id')
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->where('transactions.user_id', Auth::id())
            ->selectRaw('categories.name, SUM(transactions.amount) as amount')
            ->groupBy('categories.name')
            ->orderByDesc('amount')
            ->get()
            ->map(function ($category) use ($totalSpending) {
                return [
                    'name' => $category->name,
                    'amount' => $category->amount,
                    'percentage' => $totalSpending > 0 ? round(($category->amount / $totalSpending) * 100, 1) : 0,
                ];
            })
            ->toArray();
    }

    protected function getTrendData($startDate, $endDate)
    {
        $transactions = Transaction::whereBetween('date', [$startDate, $endDate])
            ->where('user_id', Auth::id())
            ->selectRaw('DATE(date) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('total', 'date');

        // Fill missing dates with 0
        $trendData = [];
        $currentDate = clone $startDate;

        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            $trendData[$dateString] = $transactions[$dateString] ?? 0;
            $currentDate->addDay();
        }

        return $trendData;
    }

    public function getWeeklyData(Request $request)
    {
        $data = Transaction::where('user_id', Auth::id())
            ->selectRaw('YEARWEEK(date, 1) as week, MIN(date) as first_date, MAX(date) as last_date, SUM(amount) as total')
            ->groupBy('week')
            ->orderBy('week', 'desc')
            ->amount_limit(8)
            ->get();

        $labels = [];
        $values = [];
        
        foreach ($data as $item) {
            $start = Carbon::parse($item->first_date)->format('M j');
            $end = Carbon::parse($item->last_date)->format('M j, Y');
            $labels[] = "Week of $start - $end";
            $values[] = $item->total;
        }

        return [
            'weeklyLabels' => $labels,
            'weeklyValues' => $values,
        ];
    }
}