<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    // Show login form
    public function showLoginForm()
    {
        return view('login');
    }

    // Handle login submission
    public function login(Request $request)
    {
        // Validate the login input
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'regex:/^[^@]+@[^@]+\.[a-z]{2,}$/i' // Ensure full domain like gmail.com
            ],
            'password' => [
                'required',
             
                
            ],
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.regex' => 'Please use a full email domain (e.g. @gmail.com).',
            'password.required' => 'Password is required.',
        ]);

        // Check if user exists
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return back()->withErrors(['email' => 'No account found with this email.']);
        }

        // Check password match
        if (!Hash::check($validated['password'], $user->password)) {
            return back()->withErrors(['password' => 'Invalid password.']);
        }

        // Log user in
        session(['user' => $user]);

        // Redirect to dashboard with success message
        return redirect()->route('dashboard')->with('success', 'Login successful!');
    }

    // Logout
    public function logout()
    {
        session()->forget('user');
        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }
}
