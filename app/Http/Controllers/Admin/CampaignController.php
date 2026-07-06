<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CampaignRequest;
use App\Jobs\SendCampaignJob;
use App\Models\Coupon;
use App\Models\EmailCampaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CampaignController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:campaigns.view', only: ['index']),
            new Middleware('can:campaigns.create', only: ['create', 'store']),
            new Middleware('can:campaigns.edit', only: ['edit', 'update']),
            new Middleware('can:campaigns.delete', only: ['destroy']),
            new Middleware('can:campaigns.send', only: ['send']),
        ];
    }

    public function index(Request $request): View
    {
        $campaigns = EmailCampaign::query()
            ->with('coupon:id,code')
            ->when($request->filled('search'), fn ($q) => $q->where('subject', 'like', '%' . $request->string('search') . '%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('id')
            ->paginate(per_page())
            ->withQueryString();

        return view('admin.campaigns.index', [
            'campaigns' => $campaigns,
            'filters' => $request->only('search', 'status'),
            'stats' => [
                'total' => EmailCampaign::count(),
                'sent' => EmailCampaign::where('status', 'sent')->count(),
                'scheduled' => EmailCampaign::where('status', 'scheduled')->count(),
                'recipients' => (int) EmailCampaign::sum('sent_count'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.campaigns.create', [
            'campaign' => new EmailCampaign(['audience' => 'subscribers']),
            'coupons' => $this->coupons(),
        ]);
    }

    public function store(CampaignRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = ! empty($data['scheduled_at']) ? 'scheduled' : 'draft';
        $data['created_by'] = auth()->id();

        $campaign = EmailCampaign::create($data);

        return redirect()->route('admin.campaigns.edit', $campaign)->with('status', 'Campaign saved as ' . $campaign->status . '.');
    }

    public function edit(EmailCampaign $campaign): View
    {
        return view('admin.campaigns.edit', [
            'campaign' => $campaign,
            'coupons' => $this->coupons(),
        ]);
    }

    public function update(CampaignRequest $request, EmailCampaign $campaign): RedirectResponse
    {
        abort_unless($campaign->isEditable(), 403, 'A sent campaign can’t be edited.');

        $data = $request->validated();
        $data['status'] = ! empty($data['scheduled_at']) ? 'scheduled' : 'draft';

        $campaign->update($data);

        return back()->with('status', 'Campaign updated.');
    }

    public function destroy(EmailCampaign $campaign): RedirectResponse
    {
        $campaign->delete();

        return redirect()->route('admin.campaigns.index')->with('status', 'Campaign deleted.');
    }

    /** Send the campaign now (queues the fan-out job). */
    public function send(EmailCampaign $campaign): RedirectResponse
    {
        if (! $campaign->isEditable()) {
            return back()->with('error', 'This campaign has already been sent.');
        }

        SendCampaignJob::dispatch($campaign->id);

        return back()->with('status', 'Campaign is sending — recipients will receive it shortly.');
    }

    /** @return array<int, string> */
    private function coupons(): array
    {
        return Coupon::where('is_active', true)->orderBy('code')->pluck('code', 'id')->all();
    }
}
