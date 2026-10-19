<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Monday morning (America/Chicago) summary of the previous week's growth numbers.
Schedule::command('stats:weekly-digest')
    ->weeklyOn(1, '8:45')
    ->timezone('America/Chicago')
    ->onOneServer();
