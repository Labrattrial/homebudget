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
        $currentMonth = date('Y-m');

        // Fetch transactions for the current month
        $transactions = Transaction::where('user_id', $user->id)
            ->where('date', 'like', $currentMonth.'%')
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
        $categoryAnalysis = array();
        $transactionsByCategory = $transactions->groupBy('category_id');
        
        foreach ($transactionsByCategory as $categoryId => $categoryTransactions) {
            $categoryName = isset($categories[$categoryId]) ? $categories[$categoryId]->name : 'Unknown';
            $spent = $categoryTransactions->sum('amount');
            $budget = isset($categoryBudgets[$categoryId]) ? $categoryBudgets[$categoryId]->limit : null;
            $remaining = $budget !== null ? $budget - $spent : null;

            $categoryAnalysis[] = array(
                'id' => $categoryId,
                'name' => $categoryName,
                'spent' => $spent,
                'budget' => $budget,
                'remaining' => $remaining,
            );
        }

        // Category breakdown (for pie chart)
        $categoryData = array();
        foreach ($categoryAnalysis as $category) {
            $categoryData[] = array(
                'name' => $category['name'],
                'total' => $category['spent'],
            );
        }

        return view('pages.dashboard', array(
            'user' => $user,
            'totalExpenses' => $totalExpenses,
            'currentMonth' => $currentMonth,
            'categoryAnalysis' => $categoryAnalysis,
            'categoryData' => $categoryData,
        ));
    }

    public function getCategorySummary()
    {
        $user = Auth::user();
        $currentMonth = date('Y-m');
        
        $transactions = Transaction::where('user_id', $user->id)
            ->where('date', 'like', $currentMonth.'%')
            ->with('category')
            ->get();
            
        $summary = array();
        $groupedTransactions = $transactions->groupBy(function($item) {
            return $item->category ? $item->category->name : 'Uncategorized';
        });
        
        foreach ($groupedTransactions as $categoryName => $categoryTransactions) {
            $summary[$categoryName] = $categoryTransactions->sum('amount');
        }
            
        return response()->json($summary);
    }
}