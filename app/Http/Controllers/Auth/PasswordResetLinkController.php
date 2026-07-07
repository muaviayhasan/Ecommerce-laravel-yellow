<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * "Forgot password" — collects an email and sends the reset link. The link email
 * is the branded ResetPasswordMail (see User::sendPasswordResetNotification).
 */
class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Always report success to avoid leaking which emails exist.
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'If that email is registered, we’ve sent a password reset link. Please check your inbox.');
    }
}
