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

            DB::transaction(function () use ($request, $user) {
                // Get the image file and convert to binary
                $file = $request->file('profile_picture');
                
                // Validate file exists and is readable
                if (!$file || !$file->isValid()) {
                    throw new \Exception('Invalid file upload');
                }

                // Get file contents and validate
                $imageData = file_get_contents($file->getRealPath());
                if ($imageData === false) {
                    throw new \Exception('Failed to read image file');
                }

                // Log the size of the image data
                \Log::info('Read image data with size: ' . strlen($imageData), ['user_id' => $user->id]);

                // Validate image data
                if (!getimagesizefromstring($imageData)) {
                    throw new \Exception('Invalid image data');
                }
                
                // Save the binary data directly to the database
                $user->profile_picture = $imageData;
                $user->save();

                // Log successful save
                \Log::info('Profile picture saved successfully!', ['user_id' => $user->id]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated successfully!',
                'newProfilePictureUrl' => $user->profile_picture_url
            ]);
        } catch (\Exception $e) {
            \Log::error('Profile picture update failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile picture. Please try again.'
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
        $request->validate([
            'currency' => 'required|in:' . implode(',', \App\Helpers\CurrencyHelper::getAllCurrencies()),
        ]);

        $user = Auth::user();
        $user->currency = $request->currency;
        $user->save();

        // Redirect back with a success message
        return redirect()->back()->with('success', 'Currency updated successfully!');
    }
}
