<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Mail\Admin\NewQuoteRequestMail;
use App\Models\Customer;
use App\Models\Quotation;
use App\Support\Mail\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Customer-facing "Request a quote". Captures contact details + what they need,
 * saves it as a draft quotation for staff to price, and alerts the team.
 */
class QuoteRequestController extends Controller
{
    public function create(Request $request): View
    {
        return view('storefront.request-quote', [
            'product' => $request->query('product'), // optional context from a product page
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            // Optional, but must be a valid Pakistani mobile (0300-0000000) when given.
            'phone' => ['nullable', 'regex:/^03\d{2}-?\d{7}$/'],
            'company' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'phone.regex' => 'Enter a valid mobile number like 0300-0000000.',
        ]);

        $customer = Customer::firstOrCreate(
            ['email' => $data['email']],
            ['name' => $data['name'], 'phone' => $data['phone'] ?? null, 'type' => 'retail', 'price_tier' => 'retail', 'is_active' => true, 'user_id' => auth()->id()],
        );

        $notes = $data['message'];
        if (! empty($data['company'])) {
            $notes = "Company: {$data['company']}\n\n" . $notes;
        }

        $quotation = Quotation::create([
            'quotation_number' => $this->nextNumber(),
            'customer_id' => $customer->id,
            'status' => 'draft',
            'price_tier' => 'retail',
            'valid_until' => now()->addDays((int) setting('quotation', 'default_validity_days', 14)),
            'subtotal' => 0,
            'grand_total' => 0,
            'notes' => $notes,
        ]);

        $quotation->loadMissing('customer', 'items');
        $adminEmail = setting('store', 'support_email') ?: setting('mail', 'from_address') ?: config('mail.from.address');
        Notifier::send('admin_new_quote_request', $adminEmail, new NewQuoteRequestMail($quotation, route('admin.quotations.show', $quotation)));

        return redirect()->route('quote.request')->with('quote_status', 'Thanks! We’ve received your request and will get back to you shortly.');
    }

    private function nextNumber(): string
    {
        $prefix = (string) setting('numbering', 'quotation_prefix', 'QUO-');

        return $prefix . str_pad((string) ((Quotation::max('id') ?? 0) + 1), 5, '0', STR_PAD_LEFT);
    }
}
