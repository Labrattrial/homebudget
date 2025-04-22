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
        $expenses = Transaction::where('user_id', Auth::id())->get();

        // Calculate category-wise expense summary
        $categorySummary = $expenses->groupBy('category_id')->map(function ($transactions, $categoryId) {
            return $transactions->sum('amount');
        });

        // Replace category IDs with category names in the summary
        $categorySummary = $categorySummary->mapWithKeys(function ($total, $categoryId) {
            $categoryName = Category::find($categoryId)->name ?? 'Unknown';
            return [$categoryName => $total];
        });

        // Pass the variables to the view
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

        // Save expense to the database
        $expense = new Transaction();
        $expense->user_id = Auth::id(); // Associate user
        $expense->category_id = $request->category_id;
        $expense->amount = $request->amount;
        $expense->date = $request->date;
        $expense->save();

        // Return a JSON response with the newly created expense
        return response()->json([
            'success' => true,
            'data' => $expense,
        ]);
    }

    // Edit an existing transaction (expense)
    public function edit($id)
    {
        $transaction = Transaction::findOrFail($id);

        // Ensure the transaction belongs to the authenticated user
        if ($transaction->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return response()->json($transaction); // Return data as JSON for frontend
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

        // Ensure the transaction belongs to the authenticated user
        if ($transaction->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $transaction->update([
            'amount' => $request->amount,
            'category_id' => $request->category_id,
            'date' => $request->date,
        ]);

        // Return a JSON response with the updated transaction
        return response()->json([
            'success' => true,
            'data' => $transaction,
        ]);
    }

    // Delete a transaction
    public function destroy($id)
    {
        $transaction = Transaction::findOrFail($id);

        // Ensure the transaction belongs to the authenticated user
        if ($transaction->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $transaction->delete();

        // Return a JSON response indicating success
        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully.',
        ]);
    }
}