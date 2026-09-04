<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Η αυτοματοποιημένη εκτέλεση δημιουργεί μόνο προεπισκόπηση. Η οριστικοποίηση
// παραμένει ελεγχόμενη ενέργεια από την οθόνη «Αποσβέσεις».
Schedule::command('assets:calculate-depreciation --preview')->monthlyOn(1, '01:00');
Schedule::command('assets:send-alerts')->dailyAt('08:00')->withoutOverlapping();
Schedule::command('system:backup --scheduled')->everyFifteenMinutes()->withoutOverlapping(120);
Schedule::command('system:verify-backups')->dailyAt('07:00')->timezone(config('backup.timezone', 'Europe/Athens'))->withoutOverlapping(120);
