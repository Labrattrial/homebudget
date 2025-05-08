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
    public function index(Request $request)
    {
        $categories = Category::all();
        $month = $request->get('month', now()->format('Y-m'));
        
        $expenses = Transaction::where('user_id', Auth::id())
            ->where('type', 'expense')
            ->where('date', 'like', "$month%")
            ->with('category')
            ->orderBy('date', 'desc')
            ->get();

        // Fetch distinct specs (stored in the 'description' column)
        $specs = Transaction::where('type', 'expense')
            ->distinct('description')
            ->whereNotNull('description')
            ->pluck('description');

        if ($request->wantsJson()) {
            $categoryBreakdown = $categories->map(function($category) use ($expenses) {
                return [
                    'name' => $category->name,
                    'total' => $expenses->where('category_id', $category->id)->sum('amount')
                ];
            });

            return response()->json([
                'expenses' => $expenses,
                'totalExpenses' => $expenses->sum('amount'),
                'categoryBreakdown' => $categoryBreakdown
            ]);
        }

        return view('pages.expenses', compact('categories', 'expenses', 'specs'));
    }

    /**
     * Fetch descriptions for a specific category
     */
    public function getDescriptionsByCategory($categoryId)
    {
        $specs = Transaction::where('category_id', $categoryId)
            ->where('type', 'expense')
            ->where('user_id', Auth::id())
            ->distinct()
            ->pluck('description')
            ->filter()
            ->values();

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
            'specs_option' => 'nullable|in:existing,new',
            'description' => 'nullable|max:255',
            'new_specs' => 'nullable|max:255',
        ]);

        // Determine the description based on the option selected
        $description = null;
        if ($request->has('specs_option')) {
            $description = $validated['specs_option'] === 'new'
                ? $validated['new_specs']
                : $validated['description'];
        }

        $expense = Transaction::create([
            'user_id' => Auth::id(),
            'category_id' => $validated['category_id'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'description' => $description,
            'type' => 'expense'
        ]);

        // Get updated totals and category breakdown
        $month = now()->format('Y-m');
        $expenses = Transaction::where('user_id', Auth::id())
            ->where('type', 'expense')
            ->where('date', 'like', "$month%")
            ->get();

        $categories = Category::all();
        $categoryBreakdown = $categories->map(function($category) use ($expenses) {
            return [
                'name' => $category->name,
                'total' => $expenses->where('category_id', $category->id)->sum('amount')
            ];
        });

        return response()->json([
            'success' => true,
            'expense' => $expense->load('category'),
            'totalExpenses' => $expenses->sum('amount'),
            'categoryBreakdown' => $categoryBreakdown
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
            'specs_option' => 'nullable|in:existing,new',
            'description' => 'nullable|max:255',
            'new_specs' => 'nullable|max:255',
        ]);

        // Determine the description based on the option selected
        $description = null;
        if ($request->has('specs_option')) {
            $description = $validated['specs_option'] === 'new'
                ? $validated['new_specs']
                : $validated['description'];
        }

        $transaction = $this->getUserTransaction($id);
        $transaction->update([
            'category_id' => $validated['category_id'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'description' => $description,
            'type' => 'expense'
        ]);

        return response()->json([
            'success' => true,
            'expense' => $transaction->load('category'),
        ]);
    }

    /**
     * Delete an expense
     */
    public function destroy($id)
    {
        $transaction = $this->getUserTransaction($id);
        $transaction->delete();

        // Get updated totals
        $month = now()->format('Y-m');
        $expenses = Transaction::where('user_id', Auth::id())
            ->where('date', 'like', "$month%")
            ->get();

        $categories = Category::all();
        $categoryBreakdown = $categories->map(function($category) use ($expenses) {
            return [
                'name' => $category->name,
                'total' => $expenses->where('category_id', $category->id)->sum('amount')
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully.',
            'totalExpenses' => $expenses->sum('amount'),
            'categoryBreakdown' => $categoryBreakdown
        ]);
    }

    /**
     * Helper method to get a user's transaction with authorization check
     */
    private function getUserTransaction($id)
    {
        $transaction = Transaction::with ('category')->findOrFail($id);

        if ($transaction->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return $transaction;
    }
}
