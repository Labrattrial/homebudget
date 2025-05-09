<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MainDashboardController extends Controller
{
    public function showDashboard()
    {
        $user = Auth::user();
        $currentMonth = now()->format('Y-m');

        // Fetch transactions for the current month
        $transactions = Transaction::with('category')
            ->where('user_id', $user->id)
            ->where('date', 'like', "$currentMonth%")
            ->orderBy('date', 'desc')
            ->get();

        // Calculate expenses
        $currentMonthExpenses = abs($transactions->where('amount', '<', 0)->sum('amount'));

        // Get budgets
        $overallBudget = Budget::where('user_id', $user->id)
            ->where('month', $currentMonth)
            ->whereNull('category_id')
            ->value('amount_limit') ?? 0;

        // Prepare category analysis
        $categoryAnalysis = $this->getCategoryAnalysis($user, $transactions);

        // Prepare category data for the chart
        $categoryData = [];
        foreach ($categoryAnalysis as $category) {
            $categoryData[] = [
                'name' => $category['name'],
                'total' => $category['spent'],
                'color' => $category['color'],
            ];
        }

        // Calculate spending trend percentage
        $previousMonthExpenses = abs(Transaction::where('user_id', $user->id)
            ->where('date', 'like', now()->subMonth()->format('Y-m') . '%')
            ->where('amount', '<', 0)
            ->sum('amount'));
        $spendingTrendPercentage = $previousMonthExpenses > 0 ? 
            ($currentMonthExpenses / $previousMonthExpenses) * 100 : 100;

        return view('pages.dashboard', [
            'user' => $user,
            'totalExpenses' => $currentMonthExpenses,
            'currentMonth' => now()->format('F Y'),
            'categoryAnalysis' => $categoryAnalysis,
            'budget' => $overallBudget,
            'transactions' => $transactions,
            'categories' => Category::all(),
            'spendingTrendPercentage' => $spendingTrendPercentage,
            'categoryData' => $categoryData, // Pass the category data to the view
        ]);
    }

    public function getDashboardData()
    {
        $user = Auth::user();
        $currentMonth = now()->format('Y-m');

        // Fetch transactions for the current month
        $transactions = Transaction::with('category')
            ->where('user_id', $user->id)
            ->where('date', 'like', "$currentMonth%")
            ->orderBy('date', 'desc')
            ->get();

        // Calculate expenses
        $currentMonthExpenses = abs($transactions->where('amount', '<', 0)->sum('amount'));

        // Get budgets
        $overallBudget = Budget::where('user_id', $user->id)
            ->where('month', $currentMonth)
            ->whereNull('category_id')
            ->value('amount_limit') ?? 0;

        // Prepare category analysis
        $categoryAnalysis = $this->getCategoryAnalysis($user, $transactions);

        return response()->json([
            'budget' => $overallBudget,
            'totalExpenses' => $currentMonthExpenses,
            'categoryAnalysis' => $categoryAnalysis,
        ]);
    }

    public function getBudgetStatus()
    {
        $user = Auth::user();
        $currentMonth = now()->format('Y-m');
        
        // Get total budget limit (the one with null category_id)
        $totalBudget = Budget::where('user_id', $user->id)
            ->where('month', $currentMonth)
            ->whereNull('category_id')
            ->value('amount_limit') ?? 0;
        
        // Debug: Check all transactions for this user
        $allTransactions = Transaction::where('user_id', $user->id)->get();
        \Log::info('All transactions for user:', [
            'count' => $allTransactions->count(),
            'sample' => $allTransactions->take(5)->toArray(),
            'user_id' => $user->id
        ]);
        
        // Get all transactions for the current month
        $transactions = Transaction::where('user_id', $user->id)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->get();
            
        \Log::info('Current month transactions:', [
            'count' => $transactions->count(),
            'sample' => $transactions->take(5)->toArray(),
            'year' => now()->year,
            'month' => now()->month
        ]);
        
        // Calculate total spent amount (sum of all negative amounts)
        $negativeTransactions = $transactions->where('amount', '<', 0);
        $totalSpent = abs($negativeTransactions->sum('amount'));
        
        \Log::info('Negative transactions:', [
            'count' => $negativeTransactions->count(),
            'total' => $totalSpent,
            'sample' => $negativeTransactions->take(5)->toArray()
        ]);
        
        // Calculate total percentage
        $totalPercentage = $totalBudget > 0 ? ($totalSpent / $totalBudget) * 100 : 0;
        
        // Get category budgets and their spent amounts
        $categoryBudgets = Budget::where('user_id', $user->id)
            ->where('month', $currentMonth)
            ->whereNotNull('category_id')
            ->with('category')
            ->get();
        
        $categoryWarnings = [];
        foreach ($categoryBudgets as $budget) {
            // Calculate spent amount for this category (sum of negative amounts)
            $categoryTransactions = $transactions->where('category_id', $budget->category_id)
                ->where('amount', '<', 0);
            $spent = abs($categoryTransactions->sum('amount'));
            
            \Log::info("Category {$budget->category->name} transactions:", [
                'count' => $categoryTransactions->count(),
                'total' => $spent,
                'category_id' => $budget->category_id
            ]);
            
            $percentage = $budget->amount_limit > 0 ? ($spent / $budget->amount_limit) * 100 : 0;
            
            if ($percentage >= 86 || $spent > $budget->amount_limit) {
                $categoryWarnings[] = [
                    'name' => $budget->category->name,
                    'percentage' => $percentage,
                    'isExceeded' => $spent > $budget->amount_limit
                ];
            }
        }
        
        // Determine if there are any warnings
        $hasWarnings = $totalPercentage >= 86 || $totalSpent > $totalBudget || count($categoryWarnings) > 0;
        
        // Log the budget status for debugging
        \Log::info('Budget Status:', [
            'totalBudget' => $totalBudget,
            'totalSpent' => $totalSpent,
            'totalPercentage' => $totalPercentage,
            'hasWarnings' => $hasWarnings,
            'categoryWarnings' => $categoryWarnings,
            'transaction_count' => $transactions->count(),
            'negative_transactions' => $negativeTransactions->count()
        ]);
        
        return response()->json([
            'totalBudget' => $totalBudget,
            'totalSpent' => $totalSpent,
            'totalPercentage' => $totalPercentage,
            'categoryWarnings' => $categoryWarnings,
            'hasWarnings' => $hasWarnings
        ]);
    }

    private function getCategoryAnalysis($user, $transactions)
    {
        $categoryAnalysis = [];
        $colors = ['#4E79A7', '#F28E2B', '#E15759', '#76B7B2', '#59A14F', '#EDC948', '#B07AA1'];

        foreach (Category::all() as $index => $category) {
            // Only sum negative amounts (expenses)
            $spent = abs($transactions->where('category_id', $category->id)
                ->where('amount', '<', 0)
                ->sum('amount'));
                
            $categoryBudget = Budget::where('user_id', $user->id)
                ->where('month', now()->format('Y-m'))
                ->where('category_id', $category->id)
                ->first();

            $categoryAnalysis[] = [
                'id' => $category->id,
                'name' => $category->name,
                'icon' => $category->icon ?? 'shopping-bag',
                'spent' => $spent,
                'budget' => $categoryBudget ? $categoryBudget->amount_limit : 0,
                'remaining' => $categoryBudget ? ($categoryBudget->amount_limit - $spent) : 0,
                'color' => $colors[$index % count($colors)]
            ];
        }

        return $categoryAnalysis;
    }

    public function saveBudgets(Request $request)
    {
        \Log::info('Received budget save request:', $request->all());

        try {
            $validated = $request->validate([
                'month' => 'required|date_format:Y-m',
                'amount_limit' => 'required|numeric|min:0',
                'budgets' => 'required|array',
                'budgets.*.category_id' => 'required|exists:categories,id',
                'budgets.*.amount_limit' => 'required|numeric|min:0'
            ]);

            \Log::info('Validated data:', $validated);

            \DB::beginTransaction();

            // Save total budget
            $totalBudget = Budget::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'month' => $validated['month'],
                    'category_id' => null
                ],
                [
                    'amount_limit' => $validated['amount_limit'],
                    'updated_at' => now()
                ]
            );

            \Log::info('Total budget saved:', ['budget' => $totalBudget]);

            // Save category budgets
            foreach ($validated['budgets'] as $budget) {
                \Log::info('Processing category budget:', $budget);
                
                $categoryBudget = Budget::updateOrCreate(
                    [
                        'user_id' => Auth::id(),
                        'month' => $validated['month'],
                        'category_id' => $budget['category_id']
                    ],
                    [
                        'amount_limit' => $budget['amount_limit'],
                        'updated_at' => now()
                    ]
                );

                \Log::info('Category budget saved:', ['budget' => $categoryBudget]);
            }

            \DB::commit();
            \Log::info('Budget save completed successfully');

            return response()->json([
                'success' => true,
                'message' => 'Budget saved successfully!'
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Budget save failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save budget: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSpendingTrend(Request $request)
    {
        $request->validate(['period' => 'required|in:1M,3M,6M,1Y']);

        $months = [];
        $values = [];
        $count = match($request->period) {
            '1M' => 1,
            '3M' => 3,
            '6M' => 6,
            '1Y' => 12,
            default => 6
        };

        for ($i = $count - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');
            
            $monthSpending = abs(Transaction::where('user_id', Auth::id())
                ->where('date', 'like', "$monthKey%")
                ->where('amount', '<', 0)
                ->sum('amount'));
                
            $months[] = $date->format('M');
            $values[] = $monthSpending;
        }

        $trend = $count > 1 ? 
            (($values[$count-1] - $values[$count-2]) / ($values[$count-2] ?: 1)) * 100 
            : 0;

        return response()->json([
            'success' => true,
            'labels' => $months,
            'values' => $values,
            'trend' => round($trend, 1)
        ]);
    }
}