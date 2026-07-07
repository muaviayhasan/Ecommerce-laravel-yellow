<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesTableSort;
use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriberController extends Controller implements HasMiddleware
{
    use HandlesTableSort;

    public static function middleware(): array
    {
        return [
            new Middleware('can:subscribers.view', only: ['index']),
            new Middleware('can:subscribers.delete', only: ['destroy']),
            new Middleware('can:subscribers.export', only: ['export']),
        ];
    }

    public function index(Request $request): View
    {
        $subscribers = NewsletterSubscriber::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(fn ($q) => $q->where('email', 'like', $term)->orWhere('name', 'like', $term));
            })
            ->when($request->input('status') === 'active', fn ($q) => $q->whereNull('unsubscribed_at'))
            ->when($request->input('status') === 'unsubscribed', fn ($q) => $q->whereNotNull('unsubscribed_at'));

        $this->applyTableSort($subscribers, $request, [
            'email' => 'email',
            'name' => 'name',
            'status' => 'unsubscribed_at',
            'subscribed' => 'subscribed_at',
            'source' => 'source',
        ], fn ($q) => $q->latest('id'));

        $perPage = $this->perPageFor($request);
        $subscribers = $subscribers->paginate($perPage)->withQueryString();

        return view('admin.subscribers.index', [
            'subscribers' => $subscribers,
            'filters' => $request->only('search', 'status', 'sort', 'dir', 'per_page'),
            'perPage' => $perPage,
            'stats' => [
                'total' => NewsletterSubscriber::count(),
                'active' => NewsletterSubscriber::whereNull('unsubscribed_at')->count(),
                'unsubscribed' => NewsletterSubscriber::whereNotNull('unsubscribed_at')->count(),
            ],
        ]);
    }

    public function destroy(NewsletterSubscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        return back()->with('status', 'Subscriber removed.');
    }

    public function export(): StreamedResponse
    {
        $filename = 'newsletter-subscribers-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Email', 'Name', 'Status', 'Subscribed at', 'Unsubscribed at', 'Source']);

            NewsletterSubscriber::orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $s) {
                    fputcsv($out, [
                        $s->email,
                        $s->name,
                        $s->unsubscribed_at ? 'Unsubscribed' : 'Active',
                        optional($s->subscribed_at)->toDateTimeString(),
                        optional($s->unsubscribed_at)->toDateTimeString(),
                        $s->source,
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
