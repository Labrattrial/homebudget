<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    // Show the signup form
    public function showSignupForm()
    {
        return view('signup');
    }

    // Handle user registration
    public function register(Request $request)
    {
        // Validate the user input with complete rules
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]+$/'
            ],
            'email' => [
                'required',
                'email',
                'regex:/^[^@]+@[^@]+\.[a-z]{2,}$/i',
                'unique:users,email'
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:8',
                'regex:/[A-Z]/', // At least one uppercase
                'regex:/[a-z]/', // At least one lowercase
                'regex:/[0-9]/', // At least one digit
            ],
        ], [
            'name.required' => 'Name is required.',
            'name.regex' => 'Name must contain only letters and spaces.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.regex' => 'Please use a full email domain (e.g. @gmail.com).',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        try {
            // Create the new user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            Log::info('User created successfully:', $user->toArray());

            // Auto-login after registration
            /** @var StatefulGuard $auth */
            $auth = auth();
            $auth->login($user);

            session()->flash('success', 'Registration successful! You are now logged in.');

            // Redirect to login or dashboard
            return redirect()->route('login');
        } catch (\Exception $e) {
            Log::error('Error during user registration: ' . $e->getMessage());

            return back()->withErrors(['error' => 'Registration failed! Please try again.']);
        }
    }
}
