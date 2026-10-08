<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\ExamSession;
use App\Models\Student;

class ExamService
{
    public function start(Student $student, Assessment $assessment): ExamSession
    {
        abort_unless($assessment->is_exam, 404);
        abort_unless(app(AssessmentService::class)->canStudentAttempt($student, $assessment), 403);

        return ExamSession::firstOrCreate(
            ['assessment_id' => $assessment->id, 'student_id' => $student->id],
            ['started_at' => now(), 'expires_at' => now()->addMinutes($assessment->duration_minutes ?? 0), 'status' => 'active']
        );
    }

    public function remainingSeconds(ExamSession $session): int
    {
        return (int) max(0, now()->diffInSeconds($session->expires_at, false));
    }

    public function autoSubmitExpired(): int
    {
        $count = 0;
        ExamSession::where('status', 'active')->where('expires_at', '<=', now())->each(function (ExamSession $session) use (&$count) {
            $exists = \App\Models\Submission::where('assessment_id', $session->assessment_id)->where('student_id', $session->student_id)->exists();
            if (! $exists) {
                app(AssessmentService::class)->submit($session->student, $session->assessment, ['answers' => []], true);
                $count++;
            }
        });

        return $count;
    }
}
