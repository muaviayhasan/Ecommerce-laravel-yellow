<?php

namespace App\Models;

use App\Mail\ResetPasswordMail;
use App\Mail\VerifyEmailMail;
use App\Support\Mail\Notifier;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'provider',
        'provider_id',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted',
        ];
    }

    // Notifications -----------------------------------------------------------

    /** Send the branded email-verification message (gated by the settings toggle). */
    public function sendEmailVerificationNotification(): void
    {
        $minutes = (int) config('auth.verification.expire', 60);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes($minutes), [
            'id' => $this->getKey(),
            'hash' => sha1($this->getEmailForVerification()),
        ]);

        Notifier::send('email_verification', $this->email, new VerifyEmailMail($this, $url, $minutes));
    }

    /** Send the branded password-reset message (gated by the settings toggle). */
    public function sendPasswordResetNotification($token): void
    {
        $minutes = (int) config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        $url = route('password.reset', ['token' => $token, 'email' => $this->getEmailForPasswordReset()]);

        Notifier::send('password_reset', $this->email, new ResetPasswordMail($this, $url, $minutes));
    }

    // Authorisation -----------------------------------------------------------

    /** Staff may sign in to the admin panel — anyone holding a role other than the storefront customer. */
    public function isStaff(): bool
    {
        return $this->roles()->where('name', '!=', 'customer')->exists();
    }

    // Accessors ---------------------------------------------------------------

    /** Resolve the avatar to a usable URL — social providers give a full URL, uploads give a storage path. */
    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        return \Illuminate\Support\Str::startsWith($this->avatar, ['http://', 'https://'])
            ? $this->avatar
            : \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar);
    }

    // Relations ----------------------------------------------------------------

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'author_id');
    }
}
