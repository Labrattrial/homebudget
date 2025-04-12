<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LogoutController;

// Home Route (redirect to login)
Route::get('/', function () {
    return redirect()->route('login'); // Redirect to login page
});

// Login Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login'); // Show login form
Route::post('/login', [LoginController::class, 'login']); // Handle login form submission

// Signup Routes
Route::get('/signup', [RegisterController::class, 'showSignupForm'])->name('signup'); // Show signup form
Route::post('/signup', [RegisterController::class, 'register']); // Handle signup form submission

// Logout Route
Route::get('/logout', [LoginController::class, 'logout'])->name('logout'); // Handle logout

// Dashboard Route
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
