<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BudgetController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $currentMonth = now()->format('Y-m');
        
        // Get all categories with their current budget
        $categories = Category::with(['budget' => function($query) use ($currentMonth) {
            $query->where('month', $currentMonth);
        }])->get();
        
        $hasBudget = Budget::where('user_id', $user->id)
            ->where('month', $currentMonth)
            ->exists();
            
        $totalBudget = Budget::where('user_id', $user->id)
            ->where('month', $currentMonth)
            ->sum('amount');
            
        $spentAmount = Transaction::where('user_id', $user->id)
            ->whereBetween('date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString()
            ])
            ->where('type', 'expense')
            ->sum('amount');
            
        // Prepare category budgets with spent amounts
        $categoryBudgets = [];
        foreach ($categories as $category) {
            $spent = Transaction::where('user_id', $user->id)
                ->where('category_id', $category->id)
                ->whereBetween('date', [
                    now()->startOfMonth()->toDateString(),
                    now()->endOfMonth()->toDateString()
                ])
                ->where('type', 'expense')
                ->sum('amount');
                
            $categoryBudgets[] = [
                'id' => $category->id,
                'name' => $category->name,
                'icon' => $category->icon,
                'amount' => $category->budget->amount ?? 0,
                'spent' => $spent
            ];
        }
        
        // Recent transactions
        $recentTransactions = Transaction::with('category')
            ->where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get()
            ->map(function($transaction) {
                return [
                    'description' => $transaction->description,
                    'category' => $transaction->category->name,
                    'icon' => $transaction->category->icon,
                    'amount' => $transaction->amount,
                    'type' => $transaction->type,
                    'date' => $transaction->date
                ];
            });
            
        return view('pages.dashboard', compact(
            'user',
            'hasBudget',
            'categories',
            'totalBudget',
            'spentAmount',
            'categoryBudgets',
            'recentTransactions'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'month' => 'required|date_format:Y-m',
        ]);

        Budget::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'category_id' => $request->category_id,
                'month' => $request->month,
            ],
            [
                'amount' => $request->amount,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Budget saved successfully',
        ]);
    }

    public function clear(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        Budget::where('user_id', Auth::id())
            ->where('month', $request->month)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Budgets cleared successfully',
        ]);
    }
}
