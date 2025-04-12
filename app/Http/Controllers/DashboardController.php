<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;

class DashboardController extends Controller
{
    public function index()
    {
        // Fetch net worth (sum of transactions)
        $netWorth = Transaction::sum('amount'); // Assuming 'amount' is the column for transaction value
        
        // Get all categories (used in the charts)
        $categories = Category::all();

        // You can fetch other data you need for the dashboard, such as recent transactions
        // if necessary
        $transactions = Transaction::all();

        // Return the data to the dashboard view
        return view('dashboard', compact('netWorth', 'categories', 'transactions'));
    }
}

