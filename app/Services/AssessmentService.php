<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Assessment;
use App\Models\AuditLog;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Student;
use App\Models\Submission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssessmentService
{
    public function canStudentAccess(Student $student, Assessment $assessment): bool
    {
        $grade = app(AcademicYearService::class)->studentExpectedGrade($student);

        return $grade !== null && $assessment->target_grade <= $grade;
    }

    public function canStudentAttempt(Student $student, Assessment $assessment): bool
    {
        $grade = app(AcademicYearService::class)->studentExpectedGrade($student);

        return $grade !== null && $assessment->target_grade === $grade;
    }

    public function submit(Student $student, Assessment $assessment, array $payload, bool $auto = false): Submission
    {
        abort_unless($this->canStudentAttempt($student, $assessment), 403);

        return DB::transaction(function () use ($student, $assessment, $payload, $auto) {
            $submission = Submission::create([
                'assessment_id' => $assessment->id,
                'student_id' => $student->id,
                'attempt_no' => 1,
                'submitted_at' => now(),
                'status' => $auto ? 'auto_submitted' : 'submitted',
            ]);

            $score = 0;
            $needsManual = false;

            $assessment->questions()->with('options')->get()->each(function (Question $question) use ($submission, $payload, &$score, &$needsManual) {
                $answer = $payload['answers'][$question->id] ?? null;
                $isCorrect = null;
                $points = null;

                if ($question->type === 'multiple_choice') {
                    $option = QuestionOption::where('question_id', $question->id)->whereKey($answer)->first();
                    $isCorrect = (bool) $option?->is_correct;
                    $points = $isCorrect ? $question->points : 0;
                } elseif ($question->type === 'true_false') {
                    $isCorrect = strtolower((string) $answer) === strtolower((string) $question->correct_answer);
                    $points = $isCorrect ? $question->points : 0;
                } else {
                    $needsManual = true;
                }

                if ($points !== null) {
                    $score += $points;
                }

                Answer::create([
                    'submission_id' => $submission->id,
                    'question_id' => $question->id,
                    'answer_text' => is_scalar($answer) ? (string) $answer : null,
                    'is_correct' => $isCorrect,
                    'points_awarded' => $points,
                ]);
            });

            $submission->update([
                'status' => $needsManual ? 'submitted' : 'graded',
                'score' => $needsManual ? null : $score,
            ]);

            if (! $needsManual) {
                DB::table('grades')->insert([
                    'submission_id' => $submission->id,
                    'score' => $score,
                    'status' => 'final',
                    'graded_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($assessment->is_exam) {
                ExamSession::where('assessment_id', $assessment->id)
                    ->where('student_id', $student->id)
                    ->update(['status' => $auto ? 'expired' : 'submitted', 'submitted_at' => now()]);
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => $auto ? 'exam_auto_submitted' : 'assessment_submitted',
                'entity_type' => Assessment::class,
                'entity_id' => $assessment->id,
                'created_at' => now(),
            ]);

            return $submission->fresh();
        });
    }

    public function manualGrade(Submission $submission, float $score, ?string $feedback): void
    {
        DB::transaction(function () use ($submission, $score, $feedback) {
            $submission->update(['score' => $score, 'feedback' => $feedback, 'status' => 'graded']);
            DB::table('grades')->updateOrInsert(
                ['submission_id' => $submission->id],
                ['teacher_id' => Auth::user()->teacher->id, 'score' => $score, 'feedback' => $feedback, 'status' => 'final', 'graded_at' => now(), 'updated_at' => now(), 'created_at' => now()]
            );
        });
    }
}
