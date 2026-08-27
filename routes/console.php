<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reports:check-deadlines')->daily()->timezone('Europe/Riga');
Schedule::command('tasks:check-deadlines')->dailyAt('09:00')->timezone('Europe/Riga');
Schedule::command('digest:daily')->dailyAt('09:00')->timezone('Europe/Riga');
Schedule::command('gmail:watch')->dailyAt('04:00')->timezone('Europe/Riga');
Schedule::command('gmail:sync-recent')->everyTwoMinutes();
