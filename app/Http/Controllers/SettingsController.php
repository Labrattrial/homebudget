<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;


class SettingsController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = Auth::user();


        if (!$user || !$user instanceof \App\Models\User) {
            return redirect()->back()->withErrors(['error' => 'Authenticated user is not valid.']);
        }

        try {
            $request->validate([
                'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($request->hasFile('profile_picture')) {
                // Delete old profile picture if exists
                if ($user->profile_picture && Storage::exists($user->profile_picture)) {
                    Storage::delete($user->profile_picture);
                }

                $path = $request->file('profile_picture')->store('public/profile_pictures');
                $user->profile_picture = Storage::url($path);
                $user->save();
                return redirect()->back()->with('success', 'Profile picture updated successfully!');
            }

            return redirect()->back()->with('info', 'No changes were made.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update profile picture.');
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

            $user->password = Hash::make($request->new_password);
            $user->save();

            return redirect()->back()->with('success', 'Password updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update password.');
        }
    }
}