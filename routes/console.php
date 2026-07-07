<?php

use App\Jobs\SendAbandonedCartRemindersJob;
use App\Jobs\SendCampaignJob;
use App\Models\EmailCampaign;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Dispatch any scheduled email campaigns whose time has arrived. Requires the
// scheduler cron in production: `php artisan schedule:run` every minute.
Schedule::call(function () {
    EmailCampaign::query()
        ->where('status', 'scheduled')
        ->whereNotNull('scheduled_at')
        ->where('scheduled_at', '<=', now())
        ->get()
        ->each(function (EmailCampaign $campaign) {
            $campaign->update(['status' => 'sending']); // claim it so the next tick skips it
            SendCampaignJob::dispatch($campaign->id);
        });
})->everyMinute()->name('dispatch-due-campaigns')->withoutOverlapping();

// Sweep abandoned carts and send any reminders that are due. The job self-gates
// on the "emails.abandoned_cart" toggle, so this is a cheap no-op while off.
Schedule::job(new SendAbandonedCartRemindersJob)
    ->everyFifteenMinutes()
    ->name('abandoned-cart-reminders')
    ->withoutOverlapping();

// Roll up Horizon queue metrics for the dashboard (only meaningful on Redis).
if (config('queue.default') === 'redis' && class_exists(\Laravel\Horizon\Horizon::class)) {
    Schedule::command('horizon:snapshot')->everyFiveMinutes();
}

// Prune resolved error logs past their retention window (Settings → System).
Schedule::call(function () {
    $days = (int) setting('system', 'error_log_retention_days', 30);
    if ($days > 0) {
        \App\Models\ErrorLog::whereNotNull('resolved_at')
            ->where('resolved_at', '<=', now()->subDays($days))
            ->delete();
    }
})->daily()->name('prune-error-logs')->withoutOverlapping();
