<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The signed-in staff member's own account: details, photo and password.
 * Reachable from the admin header user-menu (any staff user, no extra permission).
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $this->ensureStaff($request);

        return view('admin.profile.edit', ['user' => $request->user()]);
    }

    /** Update name / email / phone / avatar. */
    public function update(Request $request): RedirectResponse
    {
        $user = $this->ensureStaff($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            $this->deleteUploadedAvatar($user->avatar);
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } else {
            unset($data['avatar']); // don't overwrite the existing photo
        }

        $user->fill($data)->save();

        return back()->with('status', 'Profile updated.');
    }

    /** Change password (requires the current one). */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $this->ensureStaff($request);

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = (string) $request->string('password'); // hashed by the model cast
        $user->save();

        return back()->with('status', 'Password updated.');
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $this->ensureStaff($request);

        $this->deleteUploadedAvatar($user->avatar);
        $user->update(['avatar' => null]);

        return back()->with('status', 'Profile photo removed.');
    }

    /** Only staff manage a profile here; block stray customer sessions. */
    private function ensureStaff(Request $request): \App\Models\User
    {
        $user = $request->user();
        abort_unless($user && $user->isStaff(), 403);

        return $user;
    }

    /** Remove a previously uploaded avatar file (never touch social-provider URLs). */
    private function deleteUploadedAvatar(?string $avatar): void
    {
        if ($avatar && ! Str::startsWith($avatar, ['http://', 'https://'])) {
            Storage::disk('public')->delete($avatar);
        }
    }
}
