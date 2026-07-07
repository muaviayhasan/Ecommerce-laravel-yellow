<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Mail\Admin\ContactMessageMail;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Product;
use App\Support\Mail\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Informational storefront pages (About, Contact). Contact submissions are
 * emailed to the store's support address (gated by the emails toggle), with the
 * sender set as reply-to so staff can answer in one click.
 */
class PageController extends Controller
{
    /** Pakistani mobile number in 0300-0000000 form (dash optional). */
    private const PHONE_RULE = 'regex:/^03\d{2}-?\d{7}$/';

    public function about(): View
    {
        return view('storefront.about', [
            'stats' => [
                'products' => Product::where('is_active', true)->where('is_web_listed', true)->count(),
                'brands' => Brand::where('is_active', true)->count(),
                'orders' => Order::whereIn('status', ['delivered', 'completed'])->count(),
            ],
        ]);
    }

    public function contact(): View
    {
        return view('storefront.contact');
    }

    public function sendContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', self::PHONE_RULE],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'phone.regex' => 'Enter a valid mobile number like 0300-0000000.',
        ]);

        $adminEmail = setting('store', 'support_email') ?: setting('mail', 'from_address') ?: config('mail.from.address');
        Notifier::send('admin_new_contact', $adminEmail, new ContactMessageMail($data));

        return redirect()->route('contact')->with('contact_status', 'Thanks for reaching out! We’ll reply as soon as we can.');
    }
}
