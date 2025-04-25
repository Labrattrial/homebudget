<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

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
        
        // Get top category
        $topCategory = collect($categoryBreakdown)->sortByDesc('amount')->first();
        
        return view('pages.analysis', [
            'totalSpending' => $metrics['totalSpending'],
            'dailyAverage' => $metrics['dailyAverage'],
            'topCategory' => $topCategory['name'] ?? 'N/A',
            'topCategoryAmount' => $topCategory['amount'] ?? 0,
            'categoryBreakdown' => $categoryBreakdown,
            'trendDates' => array_keys($trendData),
            'trendAmounts' => array_values($trendData),
            'categoryNames' => collect($categoryBreakdown)->pluck('name')->toArray(),
            'categoryAmounts' => collect($categoryBreakdown)->pluck('amount')->toArray(),
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
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
            
            // Validate date range (max 1 year)
            if ($startDate->diffInDays($endDate) > 365) {
                throw new \Exception('Date range cannot exceed 1 year');
            }
            
            // Get transactions for metrics calculation
            $transactions = $this->getTransactions($startDate, $endDate);
            $metrics = $this->calculateMetrics($transactions, $startDate, $endDate);
            
            // Get all necessary data
            $trendData = $this->getTrendData($startDate, $endDate);
            $categoryBreakdown = $this->getCategoryBreakdown($startDate, $endDate);
            
            return response()->json([
                'trendDates' => array_keys($trendData),
                'trendAmounts' => array_values($trendData),
                'categoryNames' => collect($categoryBreakdown)->pluck('name')->toArray(),
                'categoryAmounts' => collect($categoryBreakdown)->pluck('amount')->toArray(),
                'totalSpending' => $metrics['totalSpending'],
                'dailyAverage' => $metrics['dailyAverage'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Invalid date range',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    protected function getWeeklyDataForRange($startDate, $endDate)
    {
        $data = Transaction::where('user_id', Auth::id())
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('YEARWEEK(date, 1) as week, 
                        MIN(date) as first_date, 
                        MAX(date) as last_date, 
                        SUM(amount) as total')
            ->groupBy('week')
            ->orderBy('week', 'desc')
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
            'labels' => $labels,
            'values' => $values,
        ];
    }

    protected function getMonthlyDataForRange($startDate, $endDate)
    {
        $data = Transaction::where('user_id', Auth::id())
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('DATE_FORMAT(date, "%Y-%m") as month, 
                        MIN(date) as first_date, 
                        MAX(date) as last_date, 
                        SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();
    
        $labels = [];
        $values = [];
        
        foreach ($data as $item) {
            $start = Carbon::parse($item->first_date)->format('M j, Y');
            $end = Carbon::parse($item->last_date)->format('M j, Y');
            $labels[] = "$start to $end";
            $values[] = $item->total;
        }
    
        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
    


    public function getMonthlyData(Request $request)
{
    $data = Transaction::where('user_id', Auth::id())
        ->selectRaw('DATE_FORMAT(date, "%Y-%m") as month, 
                    MIN(date) as first_date, 
                    MAX(date) as last_date, 
                    SUM(amount) as total')
        ->groupBy('month')
        ->orderBy('month', 'desc')
        ->limit(6)
        ->get();

    $labels = [];
    $values = [];
    
    foreach ($data as $item) {
        $start = Carbon::parse($item->first_date)->format('M j, Y');
        $end = Carbon::parse($item->last_date)->format('M j, Y');
        $labels[] = "$start to $end";
        $values[] = $item->total;
    }

    return [
        'monthlyLabels' => $labels,
        'monthlyValues' => $values,
    ];
}

    protected function getTransactions($startDate, $endDate)
    {
        return Transaction::whereBetween('date', [$startDate, $endDate])
            ->where('user_id', Auth::id())
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
        
        return Category::with(['transactions' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate])
                      ->where('user_id', Auth::id());
            }])
            ->get()
            ->map(function($category) use ($totalSpending) {
                $amount = $category->transactions->sum('amount');
                
                return [
                    'name' => $category->name,
                    'amount' => $amount,
                    'percentage' => $totalSpending > 0 ? round(($amount / $totalSpending) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('amount')
            ->values()
            ->toArray();
    }

    protected function getTrendData($startDate, $endDate)
{
    $trendData = [];
    $currentDate = clone $startDate;
    
    // Initialize all dates in range with 0 value
    while ($currentDate <= $endDate) {
        $dateString = $currentDate->format('M j, Y'); // Format: "Apr 1, 2025"
        $trendData[$dateString] = 0;
        $currentDate->addDay();
    }
    
    // Get actual transaction data
    $transactions = Transaction::whereBetween('date', [$startDate, $endDate])
        ->where('user_id', Auth::id())
        ->selectRaw('DATE(date) as date, SUM(amount) as total')
        ->groupBy('date')
        ->get();
    
    // Merge actual data with initialized dates
    foreach ($transactions as $transaction) {
        $dateString = Carbon::parse($transaction->date)->format('M j, Y');
        $trendData[$dateString] = (float)$transaction->total;
    }
    
    return $trendData;
}

public function getWeeklyData(Request $request)
{
    $data = Transaction::where('user_id', Auth::id())
        ->selectRaw('YEARWEEK(date, 1) as week, MIN(date) as first_date, MAX(date) as last_date, SUM(amount) as total')
        ->groupBy('week')
        ->orderBy('week', 'desc')
        ->limit(8)
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