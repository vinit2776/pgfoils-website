<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly backup (database + uploaded files), encrypted with BACKUP_ARCHIVE_PASSWORD.
// An off-site copy is required in production: add a second disk to config/backup.php destination.disks.
use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:clean')->dailyAt('01:30');
Schedule::command('backup:run')->dailyAt('02:00');
Schedule::command('backup:monitor')->dailyAt('03:00');
