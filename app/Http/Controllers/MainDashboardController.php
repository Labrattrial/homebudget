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
    $validated = $request->validate([
        'month' => 'required|date_format:Y-m',
        'amount_limit' => 'required|numeric|min:0',
        'category_id' => 'nullable|exists:categories,id'
    ]);

    \Log::debug('Validated data:', $validated); // Debug log

    try {
        \DB::beginTransaction();

        $budget = Budget::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'month' => $validated['month'],
                'category_id' => $validated['category_id']
            ],
            [
                'amount_limit' => $validated['amount_limit'], // Ensure this is included
                'updated_at' => now()
            ]
        );

        \DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Budget saved successfully!',
            'budget' => $budget
        ]);

    } catch (\Exception $e) {
        \DB::rollBack();
        \Log::error('Budget save failed:', ['error' => $e->getMessage()]);
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