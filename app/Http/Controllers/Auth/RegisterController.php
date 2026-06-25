<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisterController extends Controller
{
    /**
     * Show the registration form (bounce home if already signed in).
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.register');
    }

    /**
     * Create the account. A web sign-up creates a `users` auth row plus a linked
     * `customers` record (§10), assigns the `customer` role, then signs in.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'terms' => ['accepted'],
        ]);

        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'], // hashed by the model's 'hashed' cast
                'is_active' => true,
            ]);

            $user->customer()->create([
                'name' => $user->name,
                'email' => $user->email,
                'type' => Customer::TYPE_RETAIL,
                'price_tier' => 'retail',
                'is_active' => true,
            ]);

            if (Role::where('name', 'customer')->where('guard_name', 'web')->exists()) {
                $user->assignRole('customer');
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }
}
