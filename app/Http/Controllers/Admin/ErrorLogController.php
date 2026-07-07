<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesTableSort;
use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * Admin view over captured application exceptions (see App\Services\ErrorLogger).
 * Read-only insight plus resolve/reopen and prune actions — the rows themselves
 * are created by the exception handler, never by hand.
 */
class ErrorLogController extends Controller implements HasMiddleware
{
    use HandlesTableSort;

    public static function middleware(): array
    {
        return [
            new Middleware('can:error-logs.view', only: ['index', 'show']),
            new Middleware('can:error-logs.resolve', only: ['resolve']),
            new Middleware('can:error-logs.delete', only: ['destroy', 'clearResolved']),
        ];
    }

    public function index(Request $request): View
    {
        $logs = ErrorLog::query()
            ->with('resolver:id,name')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $query->where(fn ($q) => $q->where('message', 'like', $term)
                    ->orWhere('type', 'like', $term)
                    ->orWhere('url', 'like', $term));
            })
            ->when($request->input('status') === 'open', fn ($q) => $q->whereNull('resolved_at'))
            ->when($request->input('status') === 'resolved', fn ($q) => $q->whereNotNull('resolved_at'))
            ->when($request->filled('level'), fn ($q) => $q->where('level', $request->string('level')));

        $this->applyTableSort($logs, $request, [
            'type' => 'type',
            'level' => 'level',
            'occurrences' => 'occurrences',
            'last_seen' => 'last_seen_at',
            'status' => 'resolved_at',
            'created' => 'created_at',
        ], fn ($q) => $q->orderByRaw('resolved_at IS NULL DESC')->latest('last_seen_at'));

        $perPage = $this->perPageFor($request);

        return view('admin.error-logs.index', [
            'logs' => $logs->paginate($perPage)->withQueryString(),
            'filters' => $request->only('search', 'status', 'level', 'sort', 'dir', 'per_page'),
            'perPage' => $perPage,
            'stats' => [
                'open' => ErrorLog::whereNull('resolved_at')->count(),
                'critical' => ErrorLog::whereNull('resolved_at')->where('level', 'critical')->count(),
                'today' => ErrorLog::where('last_seen_at', '>=', now()->subDay())->count(),
                'total' => ErrorLog::count(),
            ],
        ]);
    }

    public function show(ErrorLog $errorLog): View
    {
        $errorLog->load('user:id,name', 'resolver:id,name');

        return view('admin.error-logs.show', ['log' => $errorLog]);
    }

    public function resolve(Request $request, ErrorLog $errorLog): RedirectResponse
    {
        if ($errorLog->isResolved()) {
            $errorLog->update(['resolved_at' => null, 'resolved_by' => null]);

            return back()->with('status', 'Error reopened.');
        }

        $errorLog->update(['resolved_at' => now(), 'resolved_by' => $request->user()->id]);

        return back()->with('status', 'Error marked as resolved.');
    }

    public function destroy(ErrorLog $errorLog): RedirectResponse
    {
        $errorLog->delete();

        return redirect()->route('admin.error-logs.index')->with('status', 'Error log deleted.');
    }

    public function clearResolved(): RedirectResponse
    {
        $count = ErrorLog::whereNotNull('resolved_at')->delete();

        return back()->with('status', "Cleared {$count} resolved error log(s).");
    }
}
