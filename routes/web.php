<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainDashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\UserExpensesController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\SettingsController;

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
    Route::get('/dashboard/category-summary', array('as' => 'dashboard.category-summary', 'uses' => 'MainDashboardController@getCategorySummary'));  

    // Expenses Routes
    Route::get('/expenses', [UserExpensesController::class, 'index'])->name('user.expenses'); // Display expenses
    Route::post('/expenses', [UserExpensesController::class, 'store'])->name('user.expenses.store'); // Store new expense
    Route::put('/expenses/{id}', [UserExpensesController::class, 'update']); // Update an expense
    Route::delete('/expenses/{id}', [UserExpensesController::class, 'destroy']); // Delete an expense

    Route::post('/save-budgets', [MainDashboardController::class, 'saveBudgets'])->name('saveBudgets');
    
    
    // Analysis Routes
    Route::get('/analysis', [AnalysisController::class, 'index'])->name('user.analysis');
    Route::get('/analysis/data/custom', [AnalysisController::class, 'customDateRangeData'])->name('analysis.customData');
    Route::get('/analysis/data/week/{weekNumber}', [AnalysisController::class, 'getWeekData']);
    Route::get('/analysis/data/weekly', [AnalysisController::class, 'getWeeklyData']);
    Route::get('/analysis/data/monthly', [AnalysisController::class, 'getMonthlyData']);

    // Settings Routes Group
    Route::get('/settings', function () {
        return view('pages.settings'); // this points to resources/views/pages/settings.blade.php
    })->name('user.settings');

    Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
});
    


