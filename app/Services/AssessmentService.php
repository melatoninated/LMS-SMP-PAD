<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\AssessmentDraft;
use App\Models\Course;
use App\Models\AuditLog;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Student;
use App\Models\Submission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssessmentService
{
    public function canStudentViewCourse(Student $student, Course $course): bool
    {
        $year = app(AcademicYearService::class)->active();
        if (! $year || ! $course->is_active || $course->is_deleted) return false;
        if ($course->academic_year_id !== null && $course->class_room_id !== null) {
            if ($course->academic_year_id === $year->id) return $student->class_room_id === $course->class_room_id;
            return $student->classHistories()->where('academic_year_id', $course->academic_year_id)->where('class_room_id', $course->class_room_id)->exists();
        }
        $grade = app(AcademicYearService::class)->studentExpectedGrade($student);
        return $grade !== null && $course->target_grade <= $grade;
    }

    public function canStudentAccess(Student $student, Assessment $assessment): bool
    {
        if (! $assessment->is_published) {
            return false;
        }

        $course = $assessment->section->course;
        $years = app(AcademicYearService::class);
        $activeYear = $years->active();
        if (! $this->canStudentViewCourse($student, $course)) return false;

        // New courses are bound to a specific rombel and academic year. A
        // historical course remains readable only if the student was enrolled
        // in that rombel for the course year.
        return true;
    }

    public function canStudentAttempt(Student $student, Assessment $assessment): bool
    {
        if (! $this->canStudentAccess($student, $assessment)) {
            return false;
        }

        $course = $assessment->section->course;
        $activeYear = app(AcademicYearService::class)->active();
        if ($course->academic_year_id !== null) {
            return $course->academic_year_id === $activeYear?->id
                && $course->class_room_id === $student->class_room_id;
        }

        return $assessment->target_grade === app(AcademicYearService::class)->studentExpectedGrade($student);
    }

    /**
     * Memeriksa apakah assessment sedang berada dalam jadwal pengerjaan.
     */
    public function assertWithinSchedule(Assessment $assessment): void
    {
        $now = now();

        if ($assessment->start_at !== null) {
            abort_if(
                $now->lt($assessment->start_at),
                403,
                'Assessment belum dimulai.'
            );
        }

        if ($assessment->end_at !== null) {
            abort_if(
                $now->gte($assessment->end_at),
                403,
                'Waktu pengerjaan assessment sudah berakhir.'
            );
        }
    }

    /**
     * Mengumpulkan jawaban siswa.
     *
     * Untuk ujian, submission harus memiliki sesi ujian aktif.
     * Parameter session digunakan oleh proses auto-submit.
     */
    public function submit(
        Student $student,
        Assessment $assessment,
        array $payload,
        bool $auto = false,
        ?ExamSession $session = null
    ): Submission {
        abort_unless(
            $this->canStudentAttempt($student, $assessment),
            403,
            'Siswa tidak dapat mengerjakan assessment ini.'
        );

        if (! $auto) {
            $this->assertWithinSchedule($assessment);
        }

        return DB::transaction(function () use (
            $student,
            $assessment,
            $payload,
            $auto,
            $session
        ) {
            $answers = $payload['answers'] ?? [];
            if ($auto) {
                $answers = AssessmentDraft::where('assessment_id', $assessment->id)
                    ->where('student_id', $student->id)
                    ->first()?->answers ?? $answers;
            }

            if ($assessment->is_exam) {
                $session = $session
                    ? ExamSession::whereKey($session->id)
                        ->lockForUpdate()
                        ->firstOrFail()
                    : ExamSession::where('assessment_id', $assessment->id)
                        ->where('student_id', $student->id)
                        ->where('status', 'active')
                        ->lockForUpdate()
                        ->first();

                abort_unless(
                    $session !== null
                    && $session->assessment_id === $assessment->id
                    && $session->student_id === $student->id
                    && $session->status === 'active',
                    403,
                    'Tidak ada sesi ujian aktif.'
                );

                if (! $auto) {
                    abort_if(
                        now()->gte($session->expires_at),
                        403,
                        'Waktu ujian sudah habis.'
                    );
                }

                $attemptNo = $session->attempt_no;
            } else {
                $attemptNo = (
                    Submission::where('assessment_id', $assessment->id)
                        ->where('student_id', $student->id)
                        ->max('attempt_no') ?? 0
                ) + 1;
            }

            $maximumAttempt = max(1, (int) ($assessment->maximum_attempt ?? 1));

            abort_if(
                $attemptNo > $maximumAttempt,
                403,
                'Batas maksimal percobaan assessment sudah tercapai.'
            );

            $alreadySubmitted = Submission::where('assessment_id', $assessment->id)
                ->where('student_id', $student->id)
                ->where('attempt_no', $attemptNo)
                ->exists();

            abort_if(
                $alreadySubmitted,
                409,
                'Percobaan ini sudah dikumpulkan.'
            );

            $submission = Submission::create([
                'assessment_id' => $assessment->id,
                'student_id' => $student->id,
                'attempt_no' => $attemptNo,
                'submitted_at' => now(),
                'status' => $auto ? 'auto_submitted' : 'submitted',
            ]);

            $score = 0;
            $needsManual = false;

            $assessment->questions()
                ->with('options')
                ->get()
                ->each(function (Question $question) use (
                    $submission,
                $answers,
                $payload,
                    &$score,
                    &$needsManual
                ) {
                    $answer = $answers[$question->id] ?? null;
                    $uploadedFile = ($payload['files'][$question->id] ?? null);

                    $isCorrect = null;
                    $points = null;

                    if ($question->type === 'multiple_choice') {
                        $option = QuestionOption::where(
                            'question_id',
                            $question->id
                        )
                            ->whereKey($answer)
                            ->first();

                        $isCorrect = (bool) $option?->is_correct;
                        $points = $isCorrect ? $question->points : 0;
                    } elseif ($question->type === 'true_false') {
                        $isCorrect = strtolower((string) $answer)
                            === strtolower((string) $question->correct_answer);

                        $points = $isCorrect ? $question->points : 0;
                    } else {
                        $needsManual = true;
                    }

                    $filePath = null;
                    if ($question->type === 'file_upload' && $uploadedFile instanceof UploadedFile) {
                        app(UploadPolicy::class)->validate($uploadedFile, "files.{$question->id}");
                        $filePath = $uploadedFile->store('assessment-answers', 'public');
                    }

                    if ($points !== null) {
                        $score += $points;
                    }

                    Answer::create([
                        'submission_id' => $submission->id,
                        'question_id' => $question->id,
                        'answer_text' => is_scalar($answer)
                            ? (string) $answer
                            : null,
                        'file_path' => $filePath,
                        'is_correct' => $isCorrect,
                        'points_awarded' => $points,
                    ]);
                });

            if ($needsManual) {
                $submission->update([
                    'status' => $auto ? 'auto_submitted' : 'submitted',
                    'score' => null,
                ]);
            } else {
                $submission->update([
                    'status' => 'graded',
                    'score' => $score,
                ]);

                DB::table('grades')->insert([
                    'submission_id' => $submission->id,
                    'score' => $score,
                    'status' => 'final',
                    'graded_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($assessment->is_exam && $session !== null) {
                $session->update([
                    'status' => $auto ? 'expired' : 'submitted',
                    'submitted_at' => now(),
                ]);
            }

            AssessmentDraft::where('assessment_id', $assessment->id)
                ->where('student_id', $student->id)
                ->delete();

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => $auto
                    ? 'exam_auto_submitted'
                    : 'assessment_submitted',
                'entity_type' => Assessment::class,
                'entity_id' => $assessment->id,
                'created_at' => now(),
            ]);

            return $submission->fresh();
        });
    }

    /**
     * Memberikan nilai secara manual kepada submission.
     */
    public function manualGrade(
        Submission $submission,
        float $score,
        ?string $feedback
    ): void {
        DB::transaction(function () use ($submission, $score, $feedback) {
            $submission->update([
                'score' => $score,
                'feedback' => $feedback,
                'status' => 'graded',
            ]);

            DB::table('grades')->updateOrInsert(
                ['submission_id' => $submission->id],
                [
                    'teacher_id' => Auth::user()->teacher->id,
                    'score' => $score,
                    'feedback' => $feedback,
                    'status' => 'final',
                    'graded_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        });
    }
}
