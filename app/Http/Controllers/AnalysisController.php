<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Budget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AnalysisController extends Controller
{
    public function index()
    {
        try {
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
            $currentMonth = now()->format('Y-m');
            $budget = Budget::where('user_id', Auth::id())
                            ->where('month', $currentMonth)
                            ->first();

            $budgetAmount = $budget ? $budget->amount_limit : 0;

            return view('pages.analysis', [
                'totalSpending' => $metrics['totalSpending'],
                'dailyAverage' => $metrics['dailyAverage'],
                'categoryBreakdown' => $categoryBreakdown,
                'trendDates' => array_keys($trendData),
                'trendAmounts' => array_values($trendData),
                'categoryNames' => collect($categoryBreakdown)->pluck('name')->toArray(),
                'categoryAmounts' => collect($categoryBreakdown)->pluck('amount')->toArray(),
                'budgetAmount' => $budgetAmount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in analysis index: ' . $e->getMessage());
            return view('pages.analysis')->with('error', 'Failed to load analysis data. Please try again.');
        }
    }

    public function getData(Request $request)
    {
        try {
            $startDate = Carbon::parse($request->input('start'));
            $endDate = Carbon::parse($request->input('end'));
            $viewType = $request->input('view', 'monthly');

            // Validate date range
            if ($startDate->isAfter($endDate)) {
                return response()->json(['error' => 'Start date cannot be after end date'], 400);
            }

            if ($startDate->diffInDays($endDate) > 365) {
                return response()->json(['error' => 'Date range cannot exceed 1 year'], 400);
            }

                $transactions = $this->getTransactions($startDate, $endDate);
                
                if ($transactions->isEmpty()) {
                return response()->json([
                        'totalSpending' => 0,
                        'dailyAverage' => 0,
                        'categoryNames' => [],
                        'categoryAmounts' => [],
                        'trendDates' => [],
                        'trendAmounts' => [],
                        'budgetAmount' => 0,
                        'message' => 'No data available for the selected period'
                ]);
                }

                $metrics = $this->calculateMetrics($transactions, $startDate, $endDate);
                $categoryBreakdown = $this->getCategoryBreakdown($startDate, $endDate);
                $trendData = $this->getTrendData($startDate, $endDate, $viewType);

                // Get budget amount
                $currentMonth = now()->format('Y-m');
                $budget = Budget::where('user_id', Auth::id())
                                ->where('month', $currentMonth)
                            ->whereNull('category_id') // Get total budget
                                ->first();

            return response()->json([
                    'totalSpending' => $metrics['totalSpending'],
                    'dailyAverage' => $metrics['dailyAverage'],
                    'categoryNames' => collect($categoryBreakdown)->pluck('name')->toArray(),
                    'categoryAmounts' => collect($categoryBreakdown)->pluck('amount')->toArray(),
                    'trendDates' => array_keys($trendData),
                    'trendAmounts' => array_values($trendData),
                    'budgetAmount' => $budget ? $budget->amount_limit : 0,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getData: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to load data',
                'message' => 'An error occurred while processing your request'
            ], 500);
        }
    }

    protected function getTransactions($startDate, $endDate)
    {
        return Transaction::whereBetween('date', [$startDate, $endDate])
            ->where('user_id', Auth::id())
            ->select('id', 'date', 'amount', 'category_id')
            ->with(['category:id,name'])
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
        $totalSpending = Transaction::whereBetween('date', [$startDate, $endDate])
            ->where('user_id', Auth::id())
            ->sum('amount');

        $currentMonth = now()->format('Y-m');
        
        // Get category spending and budget allocations
        $categoryData = Category::join('transactions', 'categories.id', '=', 'transactions.category_id')
            ->leftJoin('budgets', function($join) use ($currentMonth) {
                $join->on('categories.id', '=', 'budgets.category_id')
                    ->where('budgets.user_id', Auth::id())
                    ->where('budgets.month', $currentMonth);
            })
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->where('transactions.user_id', Auth::id())
            ->selectRaw('
                categories.name,
                SUM(transactions.amount) as amount,
                COALESCE(budgets.amount_limit, 0) as allocated
            ')
            ->groupBy('categories.name', 'budgets.amount_limit')
            ->orderByDesc('amount')
            ->get()
            ->map(function ($category) use ($totalSpending) {
                return [
                    'name' => $category->name,
                    'amount' => $category->amount,
                    'allocated' => $category->allocated,
                    'percentage' => $totalSpending > 0 ? round(($category->amount / $totalSpending) * 100, 1) : 0,
                ];
            })
            ->toArray();

        return $categoryData;
    }

    protected function getTrendData($startDate, $endDate, $viewType = 'monthly')
    {
        $query = Transaction::whereBetween('date', [$startDate, $endDate])
            ->where('user_id', Auth::id());

        switch ($viewType) {
            case 'daily':
                $query->selectRaw('DATE(date) as date, SUM(amount) as total')
                    ->groupBy('date');
                break;
            case 'weekly':
                $query->selectRaw('YEARWEEK(date, 1) as week, MIN(date) as date, SUM(amount) as total')
                    ->groupBy('week');
                break;
            case 'monthly':
                $query->selectRaw('DATE_FORMAT(date, "%Y-%m") as date, SUM(amount) as total')
                    ->groupBy('date');
                break;
        }

        $transactions = $query->orderBy('date', 'asc')
            ->get()
            ->pluck('total', 'date');

        // Fill missing dates with 0
        $trendData = [];
        $currentDate = clone $startDate;

        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format($viewType === 'monthly' ? 'Y-m' : 'Y-m-d');
            $trendData[$dateString] = $transactions[$dateString] ?? 0;
            
            switch ($viewType) {
                case 'daily':
                    $currentDate->addDay();
                    break;
                case 'weekly':
                    $currentDate->addWeek();
                    break;
                case 'monthly':
                    $currentDate->addMonth();
                    break;
            }
        }

        return $trendData;
    }
}