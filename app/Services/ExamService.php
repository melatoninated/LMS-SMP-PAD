<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\ExamSession;
use App\Models\Student;
use App\Models\Submission;
use Illuminate\Support\Facades\DB;

class ExamService
{
    /**
     * Memulai ujian atau melanjutkan sesi aktif.
     */
    public function start(Student $student, Assessment $assessment): ExamSession
    {
        abort_unless($assessment->is_exam, 404);

        abort_unless(
            app(AssessmentService::class)->canStudentAttempt($student, $assessment),
            403,
            'Siswa tidak dapat mengikuti ujian ini.'
        );

        app(AssessmentService::class)->assertWithinSchedule($assessment);

        // Jika siswa masih memiliki sesi aktif, gunakan sesi tersebut.
        $activeSession = ExamSession::where('assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->orderByDesc('attempt_no')
            ->first();

        if ($activeSession !== null) {
            if (now()->lt($activeSession->expires_at)) {
                return $activeSession;
            }

            // Sesi lama sudah habis waktunya. Kumpulkan otomatis.
            app(AssessmentService::class)->submit(
                $student,
                $assessment,
                ['answers' => []],
                true,
                $activeSession
            );
        }

        return DB::transaction(function () use ($student, $assessment) {
            $sessionCount = ExamSession::where('assessment_id', $assessment->id)
                ->where('student_id', $student->id)
                ->count();

            $submissionCount = Submission::where('assessment_id', $assessment->id)
                ->where('student_id', $student->id)
                ->count();

            $lastSessionAttempt = ExamSession::where(
                'assessment_id',
                $assessment->id
            )
                ->where('student_id', $student->id)
                ->max('attempt_no') ?? 0;

            $lastSubmissionAttempt = Submission::where(
                'assessment_id',
                $assessment->id
            )
                ->where('student_id', $student->id)
                ->max('attempt_no') ?? 0;

            $usedAttempts = max(
                $sessionCount,
                $submissionCount,
                $lastSessionAttempt,
                $lastSubmissionAttempt
            );

            $maximumAttempt = max(
                1,
                (int) ($assessment->maximum_attempt ?? 1)
            );

            abort_if(
                $usedAttempts >= $maximumAttempt,
                403,
                'Batas maksimal percobaan ujian sudah tercapai.'
            );

            $duration = (int) ($assessment->duration_minutes ?? 0);

            abort_if(
                $duration < 1,
                422,
                'Durasi ujian belum dikonfigurasi dengan benar.'
            );

            $attemptNo = $usedAttempts + 1;

            $startedAt = now();
            $expiresAt = $startedAt->copy()->addMinutes($duration);

            // Sesi tidak boleh melewati batas akhir jadwal ujian.
            if ($assessment->end_at !== null
                && $expiresAt->gt($assessment->end_at)) {
                $expiresAt = $assessment->end_at->copy();
            }

            return ExamSession::create([
                'assessment_id' => $assessment->id,
                'student_id' => $student->id,
                'attempt_no' => $attemptNo,
                'started_at' => $startedAt,
                'expires_at' => $expiresAt,
                'status' => 'active',
            ]);
        });
    }

    /**
     * Menghitung sisa waktu ujian dalam detik.
     */
    public function remainingSeconds(ExamSession $session): int
    {
        return (int) max(
            0,
            now()->diffInSeconds($session->expires_at, false)
        );
    }

    /**
     * Mengumpulkan otomatis seluruh sesi ujian yang sudah kedaluwarsa.
     */
    public function autoSubmitExpired(): int
    {
        $count = 0;

        ExamSession::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->each(function (ExamSession $session) use (&$count) {
                $session->refresh();

                if ($session->status !== 'active'
                    || $session->expires_at->gt(now())) {
                    return;
                }

                app(AssessmentService::class)->submit(
                    $session->student,
                    $session->assessment,
                    ['answers' => []],
                    true,
                    $session
                );

                $count++;
            });

        return $count;
    }
}
