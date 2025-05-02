<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserExpensesController extends Controller
{
    /**
     * Display all expenses for the authenticated user
     */
    public function index()
    {
        $categories = Category::all();
        $expenses = Transaction::where('user_id', Auth::id())
            ->with('category')
            ->orderBy('date', 'desc')
            ->get();

        // Fetch distinct specs (stored in the 'description' column)
        $specs = Transaction::distinct('description')
            ->whereNotNull('description')
            ->pluck('description');

        return view('pages.expenses', compact('categories', 'expenses', 'specs'));
    }

    /**
     * Fetch descriptions for a specific category
     */
    public function getDescriptionsByCategory($categoryId)
    {
        $specs = Transaction::where('category_id', $categoryId)
            ->distinct('description')
            ->whereNotNull('description')
            ->pluck('description');

        return response()->json(['specs' => $specs]);
    }

    /**
     * Store a new expense
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'specs_option' => 'required|in:existing,new',
            'description' => 'required_if:specs_option,existing|max:255',
            'new_specs' => 'required_if:specs_option,new|max:255',
        ]);

        // Determine the specs (existing or new)
        $description = $validated['specs_option'] === 'new'
            ? $validated['new_specs']
            : $validated['description'];

        $expense = Transaction::create([
            'user_id' => Auth::id(),
            'category_id' => $validated['category_id'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'description' => $description, // Save the specs in 'description'
        ]);

        return response()->json([
            'success' => true,
            'data' => $expense->load('category'),
        ]);
    }

    /**
     * Update an existing expense
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'specs_option' => 'required|in:existing,new',
            'description' => 'required_if:specs_option,existing|max:255',
            'new_specs' => 'required_if:specs_option,new|max:255',
        ]);

        // Determine the specs (existing or new)
        $description = $validated['specs_option'] === 'new'
            ? $validated['new_specs']
            : $validated['description'];

        $transaction = $this->getUserTransaction($id);
        $transaction->update([
            'category_id' => $validated['category_id'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'description' => $description,
        ]);

        return response()->json([
            'success' => true,
            'data' => $transaction->load('category'),
        ]);
    }

    /**
     * Delete an expense
     */
    public function destroy($id)
    {
        $transaction = $this->getUserTransaction($id);
        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully.',
        ]);
    }

    /**
     * Helper method to get a user's transaction with authorization check
     */
    private function getUserTransaction($id)
    {
        $transaction = Transaction::with('category')->findOrFail($id);

        if ($transaction->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return $transaction;
    }
}