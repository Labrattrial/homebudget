<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainDashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\UserExpensesController;

// Home Route (redirect to login)
Route::get('/', function () {
    return redirect()->route('login');
});

// Login Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

// Signup Routes
Route::get('/signup', [RegisterController::class, 'showSignupForm'])->name('signup');
Route::post('/signup', [RegisterController::class, 'register']);

// Logout Route
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected Routes (user must be authenticated)
Route::middleware('auth')->group(function () {
    // Dashboard + Sidebar Pages
    Route::get('/dashboard', [MainDashboardController::class, 'showDashboard'])->name('user.dashboard');
    Route::get('/analysis', fn() => view('pages.analysis'))->name('user.analysis');
    Route::get('/settings', fn() => view('pages.settings'))->name('user.settings');

    // Expenses Routes
    Route::get('/expenses', [UserExpensesController::class, 'index'])->name('user.expenses'); // Display expenses
    Route::post('/expenses', [UserExpensesController::class, 'store'])->name('user.expenses.store'); // Store new expense
    Route::put('/expenses/{id}', [UserExpensesController::class, 'update']); // Update an expense
    Route::delete('/expenses/{id}', [UserExpensesController::class, 'destroy']); // Delete an expense
});