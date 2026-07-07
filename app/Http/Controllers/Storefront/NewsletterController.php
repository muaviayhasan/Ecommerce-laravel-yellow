<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Mail\Admin\NewSubscriberMail;
use App\Models\NewsletterSubscriber;
use App\Support\Mail\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    /** Footer newsletter signup. */
    public function store(Request $request): RedirectResponse
    {
        // Validate manually so a failure can redirect back to the newsletter
        // section (#newsletter) instead of the top of the page.
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'newsletter')->withInput()->withFragment('newsletter');
        }

        $data = $validator->validated();

        $subscriber = NewsletterSubscriber::firstOrNew(['email' => $data['email']]);
        $isNew = ! $subscriber->exists;

        $subscriber->fill(['name' => $data['name'] ?? $subscriber->name, 'source' => $subscriber->source ?? 'footer']);

        // Re-activate a previously unsubscribed address.
        if ($subscriber->exists && $subscriber->unsubscribed_at) {
            $subscriber->unsubscribed_at = null;
            $subscriber->subscribed_at = now();
        }

        $subscriber->save();

        // Alert staff only the first time an address subscribes.
        if ($isNew) {
            $adminEmail = setting('store', 'support_email') ?: setting('mail', 'from_address') ?: config('mail.from.address');
            Notifier::send('admin_new_subscriber', $adminEmail, new NewSubscriberMail($subscriber));
        }

        return back()->with('newsletter_status', 'Thanks for subscribing! Keep an eye on your inbox. 🎉')->withFragment('newsletter');
    }

    /** One-click unsubscribe from the link in marketing emails. */
    public function unsubscribe(string $token): View
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->first();

        if ($subscriber && ! $subscriber->unsubscribed_at) {
            $subscriber->update(['unsubscribed_at' => now()]);
        }

        return view('storefront.newsletter-unsubscribed', ['email' => $subscriber?->email]);
    }
}
