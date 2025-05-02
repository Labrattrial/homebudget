<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class MainDashboardController extends Controller
{
    public function showDashboard()
    {
        $user = Auth::user();
        $currentMonth = now()->format('Y-m');

        // Fetch transactions for the current month
        $transactions = Transaction::where('user_id', $user->id)
            ->where('date', 'like', "$currentMonth%")
            ->get();

        // Calculate total expenses
        $totalExpenses = $transactions->sum('amount');

        // Fetch all categories (to map category_id to name)
        $categories = Category::all()->keyBy('id');

        // Get budgets for all categories (if they exist)
        $categoryBudgets = Budget::where('user_id', $user->id)
            ->where('month', $currentMonth)
            ->get()
            ->keyBy('category_id');

        // Prepare category analysis (spent, budget, remaining)
        $categoryAnalysis = $transactions->groupBy('category_id')->map(function ($categoryTransactions, $categoryId) use ($categories, $categoryBudgets) {
            $categoryName = $categories[$categoryId]->name ?? 'Unknown';
            $spent = $categoryTransactions->sum('amount');
            $budget = $categoryBudgets[$categoryId]->limit ?? null;
            $remaining = $budget !== null ? $budget - $spent : null;

            return [
                'id' => $categoryId,
                'name' => $categoryName,
                'spent' => $spent,
                'budget' => $budget,
                'remaining' => $remaining,
            ];
        });

        // Category breakdown (for pie chart)
        $categoryData = $categoryAnalysis->map(function ($category) {
            return [
                'name' => $category['name'],
                'total' => $category['spent'],
            ];
        });

        return view('pages.dashboard', [
            'user' => $user,
            'totalExpenses' => $totalExpenses,
            'currentMonth' => $currentMonth,
            'categoryAnalysis' => $categoryAnalysis,
            'categoryData' => $categoryData,
        ]);
    }

    public function saveBudgets(Request $request)
    {
        $request->validate([
            'month' => 'required|string',
            'budgets' => 'required|array',
            'budgets.*.category_id' => 'required|exists:categories,id',
            'budgets.*.limit' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();

        // Save each category budget
        foreach ($request->budgets as $budgetData) {
            Budget::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'month' => $request->month,
                    'category_id' => $budgetData['category_id'],
                ],
                [
                    'limit' => $budgetData['limit'],
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Budgets saved successfully!',
        ]);
    }
}