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
        // Add any other data you want to return
    ]);
}


private function getCategoryAnalysis($user, $transactions)
{
    $categoryAnalysis = [];
    $colors = ['#4E79A7', '#F28E2B', '#E15759', '#76B7B2', '#59A14F', '#EDC948', '#B07AA1'];

    foreach (Category::all() as $index => $category) {
        $spent = abs($transactions->where('category_id', $category->id)->sum('amount'));
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
            // First validate the basic structure
            $validated = $request->validate([
                'month' => 'required|date_format:Y-m',
                'amount_limit' => 'required|numeric|min:0',
                'budgets' => 'required|array|min:1', // Ensure at least one category budget
                'budgets.*.category_id' => 'required|exists:categories,id',
                'budgets.*.amount_limit' => 'required|numeric|min:0'
            ]);

            // Calculate total allocated amount
            $totalAllocated = collect($validated['budgets'])->sum('amount_limit');
            
            // Validate that total allocated doesn't exceed total budget
            if ($totalAllocated > $validated['amount_limit']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Total category allocations cannot exceed total budget'
                ], 422);
            }

            // Validate that at least one category has a non-zero allocation
            $hasNonZeroAllocation = collect($validated['budgets'])->contains(function ($budget) {
                return $budget['amount_limit'] > 0;
            });

            if (!$hasNonZeroAllocation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please allocate amounts to at least one category'
                ], 422);
            }

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

        } catch (\Illuminate\Validation\ValidationException $e) {
            \DB::rollBack();
            \Log::error('Budget validation failed:', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . collect($e->errors())->first()[0]
            ], 422);
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