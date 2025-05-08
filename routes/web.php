<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    MainDashboardController,
    LoginController,
    RegisterController,
    UserExpensesController,
    AnalysisController,
    SettingsController,


};
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::get('/signup', [RegisterController::class, 'showSignupForm'])->name('signup');
Route::post('/signup', [RegisterController::class, 'register']);
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [MainDashboardController::class, 'showDashboard'])->name('user.dashboard');
    Route::get('/dashboard/data', [MainDashboardController::class, 'getDashboardData'])->name('user.dashboard.data'); 
    Route::post('/dismiss-budget-warning', function() {
        session()->forget('budget_warning');
        return response()->json(['success' => true]);
    });
    Route::get('/dashboard/category-summary', [MainDashboardController::class, 'getCategorySummary'])->name('dashboard.category-summary');

    // Expenses
    Route::prefix('expenses')->group(function () {
        Route::get('/', [UserExpensesController::class, 'index'])->name('user.expenses');
        Route::post('/', [UserExpensesController::class, 'store'])->name('user.expenses.store');
        Route::put('/{id}', [UserExpensesController::class, 'update']);
        Route::delete('/{id}', [UserExpensesController::class, 'destroy']);
        Route::get('/categories/{category}/descriptions', [UserExpensesController::class, 'getDescriptionsByCategory'])->name('user.expenses.descriptions');
    });

    


    // Analysis Routes
    Route::get('/analysis', [AnalysisController::class, 'index'])->name('user.analysis');
    Route::get('/analysis/data', [AnalysisController::class, 'getData'])->name('analysis.data');
    Route::get('/analysis/data/custom', [AnalysisController::class, 'customDateRangeData'])->name('analysis.customData');
    Route::get('/analysis/data/week/{weekNumber}', [AnalysisController::class, 'getWeekData']);
    Route::get('/analysis/data/weekly', [AnalysisController::class, 'getWeeklyData']);
    Route::get('/analysis/data/monthly', [AnalysisController::class, 'getMonthlyData']);

    // Settings
    Route::prefix('settings')->group(function () {
        Route::get('/', function () {
            return view('pages.settings');
        })->name('user.settings');
        Route::post('/profile/update', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
        Route::post('/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
        Route::post('/currency', [SettingsController::class, 'updateCurrency'])->name('settings.currency.update');
    });

    // API Routes
    Route::get('/api/exchange-rate', function (Request $request) {
        try {
            $from = $request->query('from');
            $to = $request->query('to');
            
            if (!$from || !$to) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing currency parameters'
                ], 400);
            }

            $rate = \App\Helpers\CurrencyHelper::getExchangeRate($from, $to);
            
            return response()->json([
                'success' => true,
                'rate' => $rate
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    });

    // Budget Routes
    Route::post('/budgets', [MainDashboardController::class, 'saveBudgets'])->name('saveBudgets');
    Route::get('/api/spending-trend', [MainDashboardController::class, 'getSpendingTrend']);
});