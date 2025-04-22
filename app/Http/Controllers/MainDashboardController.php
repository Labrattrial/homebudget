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
    // Fetch logged-in user
    $user = Auth::user();

    // Fetch user's transactions for the current month
    $currentMonth = now()->format('Y-m');
    $transactions = Transaction::where('user_id', $user->id)
        ->where('date', 'like', "$currentMonth%")
        ->get();

    // Calculate total expenses (sum of all transactions)
    $totalExpenses = $transactions->sum('amount');

    // Get user's budget (if applicable)
    $budget = Budget::where('user_id', $user->id)
        ->where('month', $currentMonth)
        ->value('limit'); // Fetch the budget limit for the current month

    // Calculate balance
    $balance = $budget !== null ? $budget - $totalExpenses : null;

    // Fetch all categories from the database (to map category_id to name)
    $categories = Category::all()->keyBy('id');

    // Category data (group transactions by category)
    $categoryData = $transactions->groupBy('category_id')->map(function ($categoryTransactions, $categoryId) use ($categories) {
        // Get category name by ID
        $categoryName = isset($categories[$categoryId]) ? $categories[$categoryId]->name : 'Unknown';
        $totalAmount = $categoryTransactions->sum('amount');
        return [
            'name' => $categoryName,
            'total' => $totalAmount
        ];
    });

    // Make sure that empty data does not break the view
    $categoryData = $categoryData->isEmpty() ? [] : $categoryData;

    // Return the dashboard view with the data
    return view('pages.dashboard', [
        'user' => $user,
        'totalExpenses' => $totalExpenses,
        'balance' => $balance,
        'budget' => $budget,
        'categoryData' => $categoryData
    ]);
}


    public function setBudget(Request $request)
    {
        $request->validate([
            'limit' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $currentMonth = now()->format('Y-m');

        // Create or update the budget for the current month
        Budget::updateOrCreate(
            ['user_id' => $user->id, 'month' => $currentMonth],
            ['limit' => $request->limit]
        );

        return response()->json(['success' => true, 'message' => 'Budget has been set successfully.']);
    }
}