<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
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
            // Delete old profile picture if exists
            if ($user->profile_picture) {
                $oldPath = str_replace('/storage', 'public', $user->profile_picture);
                if (Storage::exists($oldPath)) {
                    Storage::delete($oldPath);
                }
            }

            $path = $request->file('profile_picture')->store('public/profile_pictures');
            $user->profile_picture = Storage::url($path);
            $user->save();
            $newProfileUrl = $user->profile_picture;
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
                'new_password' => ['required', 'string', 'min:8', 'confirmed'],
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
}
