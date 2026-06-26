<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ActivityLogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:audit.view', only: ['index']),
        ];
    }

    public function index(Request $request): View
    {
        $logs = ActivityLog::query()
            ->with('user:id,name')
            ->when($request->filled('search'), fn ($q) => $q->where('description', 'like', '%' . $request->string('search') . '%'))
            ->when($request->filled('event'), fn ($q) => $q->where('event', $request->string('event')))
            ->when($request->filled('user'), fn ($q) => $q->where('user_id', $request->integer('user')))
            ->latest('id')
            ->paginate(per_page())
            ->withQueryString();

        return view('admin.activity.index', [
            'logs' => $logs,
            'filters' => $request->only('search', 'event', 'user'),
            'users' => User::whereIn('id', ActivityLog::query()->distinct()->pluck('user_id')->filter())
                ->orderBy('name')->pluck('name', 'id'),
            'stats' => [
                'total' => ActivityLog::count(),
                'today' => ActivityLog::whereDate('created_at', today())->count(),
            ],
        ]);
    }
}
