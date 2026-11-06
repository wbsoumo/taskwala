<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic deals fetching from configured Telegram channels & EarnKaro link conversion
Schedule::command('deals:sync')->everyFifteenMinutes();

// Schedule Telegram MTProto deals & messages sync from monitored channels (cPanel Cron friendly)
Schedule::command('telegram:mtproto-sync')->everyTenMinutes();

