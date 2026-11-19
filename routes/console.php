<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('lms:expired-exams', function () {
    $count = app(App\Services\ExamService::class)->autoSubmitExpired();

    $this->info("{$count} expired exam session(s) processed.");
})->purpose('Automatically submit expired LMS exam sessions');

// Periksa sesi ujian kedaluwarsa setiap menit.
Schedule::command('lms:expired-exams')->everyMinute();
