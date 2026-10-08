<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('lms:expired-exams', function () {
    app(App\Services\ExamService::class)->autoSubmitExpired();
    $this->info('Expired exam sessions processed.');
});
