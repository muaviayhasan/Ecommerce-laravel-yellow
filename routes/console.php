<?php

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
