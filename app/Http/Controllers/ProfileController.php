<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\ProfilePhoto;
use App\Models\CoverPhoto;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $user = Auth::user();
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id], ['username' => Str::slug($user->name).'-'.$user->id]);
        return view('profile.edit', compact('user', 'profile'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'profession' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'interests' => ['nullable', 'string', 'max:500'],
            'hobbies' => ['nullable', 'string', 'max:500'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:20480'],
            'cover_photo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:20480'],
            'username' => ['sometimes', 'alpha_dash', 'min:3', 'max:50', Rule::unique('profiles')->ignore($user->profile?->id)],
            'gender' => ['nullable', 'string', 'max:40'],
            'birthday' => ['nullable', 'date', 'before:today'],
            'relationship_status' => ['nullable', 'string', 'max:60'],
            'website' => ['nullable', 'url', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'workplace' => ['nullable', 'string', 'max:160'],
            'education' => ['nullable', 'string', 'max:160'],
            'public_fields' => ['nullable', 'array'],
        ]);

        unset($validated['current_password']);
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $validated['profile_photo'] = $request->file('profile_photo')->store('profiles', 'public');
            ProfilePhoto::where('user_id', $user->id)->update(['is_current' => false]);
            ProfilePhoto::create(['user_id' => $user->id, 'path' => $validated['profile_photo'], 'is_current' => true]);
        }

        if ($request->hasFile('cover_photo')) {
            if ($user->cover_photo) {
                Storage::disk('public')->delete($user->cover_photo);
            }
            $validated['cover_photo'] = $request->file('cover_photo')->store('covers', 'public');
            CoverPhoto::where('user_id', $user->id)->update(['is_current' => false]);
            CoverPhoto::create(['user_id' => $user->id, 'path' => $validated['cover_photo'], 'is_current' => true]);
        }

        $user->update($validated);

        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id], ['username' => Str::slug($user->name).'-'.$user->id]);
        $username = $validated['username'] ?? $profile->username;
        $profile->update([
            'username' => $username, 'gender' => $validated['gender'] ?? $profile->gender,
            'birthday' => $validated['birthday'] ?? null, 'relationship_status' => $validated['relationship_status'] ?? null,
            'website' => $validated['website'] ?? null, 'contact_email' => $validated['contact_email'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'visibility' => array_key_exists('public_fields', $validated) ? array_fill_keys($validated['public_fields'], true) : $profile->visibility,
        ]);
        DB::table('user_bios')->updateOrInsert(['user_id' => $user->id], ['bio' => $validated['bio'] ?? null, 'updated_at' => now(), 'created_at' => now()]);
        DB::table('user_workplaces')->updateOrInsert(['user_id' => $user->id], ['workplace' => $validated['workplace'] ?? null, 'updated_at' => now(), 'created_at' => now()]);
        DB::table('user_education')->updateOrInsert(['user_id' => $user->id], ['education' => $validated['education'] ?? null, 'updated_at' => now(), 'created_at' => now()]);
        DB::table('user_locations')->updateOrInsert(['user_id' => $user->id], ['location' => $validated['location'] ?? null, 'updated_at' => now(), 'created_at' => now()]);
        DB::table('user_relationships')->updateOrInsert(['user_id' => $user->id], ['relationship_status' => $validated['relationship_status'] ?? null, 'updated_at' => now(), 'created_at' => now()]);

        $message = array_key_exists('password', $validated)
            ? 'Your profile and password were updated.'
            : 'Your profile was updated.';

        return to_route('people.show', $user)->with('success', $message);
    }
}
