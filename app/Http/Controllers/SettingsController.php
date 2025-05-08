<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Helpers\CurrencyHelper;

class SettingsController extends Controller
{
    private const DEFAULT_PROFILE_PICTURE = 'defaults/default-profile.png';

    public function updateProfile(Request $request)
{
    $user = Auth::user();

    if (!$user || !$user instanceof \App\Models\User) {
        return response()->json([
            'success' => false,
            'message' => 'Authenticated user is not valid.'
        ], 401);
    }

    try {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $newProfileUrl = null;

        DB::transaction(function () use ($request, $user, &$newProfileUrl) {
                // Delete old profile picture if exists and is not the default
                if ($user->profile_picture && $user->profile_picture !== self::DEFAULT_PROFILE_PICTURE) {
                    $oldPath = 'public/' . $user->profile_picture;
                if (Storage::exists($oldPath)) {
                    Storage::delete($oldPath);
                }
            }

                // Store new profile picture
                $file = $request->file('profile_picture');
                $filename = 'profile_pictures/' . time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('public', $filename);
                
                // Save the relative path to the database
                $user->profile_picture = $filename;
            $user->save();
                
                // Return the full URL for the response
                $newProfileUrl = Storage::url($filename);
        });

        return response()->json([
            'success' => true,
            'message' => 'Profile picture updated successfully!',
            'newProfilePictureUrl' => $newProfileUrl
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update profile picture: ' . $e->getMessage()
        ], 500);
    }
}

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user instanceof \App\Models\User) {
            return redirect()->back()->withErrors(['error' => 'Authenticated user is not valid.']);
        }

        try {
            $request->validate([
                'current_password' => ['required', 'string'],
                'new_password' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
                    'confirmed'
                ],
            ], [
                'new_password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
            ]);

            if (!Hash::check($request->current_password, $user->password)) {
                return redirect()->back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }

            DB::transaction(function () use ($request, $user) {
                $user->password = Hash::make($request->new_password);
                $user->save();
            });

            return redirect()->back()->with('success', 'Password updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update password: ' . $e->getMessage());
        }
    }

    public function updateCurrency(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user instanceof \App\Models\User) {
            return response()->json([
                'success' => false,
                'message' => 'Authenticated user is not valid.'
            ], 401);
        }

        try {
            $request->validate([
                'currency' => ['required', 'string', 'size:3'],
            ]);

            $validCurrencies = ['PHP', 'USD', 'EUR', 'GBP', 'JPY', 'AUD'];
            if (!in_array($request->currency, $validCurrencies)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid currency selected.'
                ], 422);
            }

            DB::transaction(function () use ($request, $user) {
                $user->currency = $request->currency;
                $user->save();
            });

            $currencySymbol = \App\Helpers\CurrencyHelper::getCurrencySymbol($request->currency);
            $currencyName = \App\Helpers\CurrencyHelper::getCurrencyName($request->currency);

            return response()->json([
                'success' => true,
                'message' => 'Currency updated successfully! Your amounts will now be displayed in ' . $currencyName . ' (' . $currencySymbol . ')',
                'currency' => $user->currency,
                'currencySymbol' => $currencySymbol,
                'currencyName' => $currencyName
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update currency: ' . $e->getMessage()
            ], 500);
        }
    }
}
