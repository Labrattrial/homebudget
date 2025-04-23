<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserExpensesController extends Controller
{
    // Display all categories and transactions
    public function index()
    {
        // Fetch categories and transactions for the authenticated user
        $categories = Category::all();
        $expenses = Transaction::where('user_id', Auth::id())
            ->with('category') // Eager load category relationship
            ->orderBy('date', 'desc')
            ->get();

        // Calculate category-wise expense summary
        $categorySummary = $this->calculateCategorySummary($expenses);

        return view('pages.expenses', compact('categories', 'expenses', 'categorySummary'));
    }

    // Store a new transaction (expense)
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);

        $expense = Transaction::create([
            'user_id' => Auth::id(),
            'category_id' => $request->category_id,
            'amount' => $request->amount,
            'date' => $request->date,
        ]);

        // Get updated expenses to calculate new summary
        $expenses = Transaction::where('user_id', Auth::id())->get();
        $categorySummary = $this->calculateCategorySummary($expenses);

        return response()->json([
            'success' => true,
            'data' => $expense->load('category'), // Load category relationship
            'categorySummary' => $categorySummary,
        ]);
    }

    // Edit an existing transaction (expense)
    public function edit($id)
    {
        $transaction = Transaction::with('category')->findOrFail($id);

        if ($transaction->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return response()->json($transaction);
    }

    // Update an existing transaction
    public function update(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'date' => 'required|date',
        ]);

        $transaction = Transaction::findOrFail($id);

        if ($transaction->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $transaction->update([
            'amount' => $request->amount,
            'category_id' => $request->category_id,
            'date' => $request->date,
        ]);

        // Get updated expenses to calculate new summary
        $expenses = Transaction::where('user_id', Auth::id())->get();
        $categorySummary = $this->calculateCategorySummary($expenses);

        return response()->json([
            'success' => true,
            'data' => $transaction->load('category'),
            'categorySummary' => $categorySummary,
        ]);
    }

    // Delete a transaction
    public function destroy($id)
    {
        $transaction = Transaction::findOrFail($id);

        if ($transaction->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $transaction->delete();

        // Get updated expenses to calculate new summary
        $expenses = Transaction::where('user_id', Auth::id())->get();
        $categorySummary = $this->calculateCategorySummary($expenses);

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully.',
            'categorySummary' => $categorySummary,
        ]);
    }

    // Helper method to calculate category summary
    private function calculateCategorySummary($expenses)
    {
        return $expenses->groupBy('category_id')->mapWithKeys(function ($transactions, $categoryId) {
            $categoryName = Category::find($categoryId)->name ?? 'Unknown';
            return [$categoryName => $transactions->sum('amount')];
        });
    }
}