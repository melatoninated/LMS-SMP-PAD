<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Question;
use App\Models\Section;
use App\Models\Submission;
use App\Services\AssessmentService;
use App\Services\ExamService;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function store(Request $request, Section $section)
    {
        $this->authorize('manage', $section->course);
        $data = $request->validate([
            'title' => ['required'], 'description' => ['nullable'], 'target_grade' => ['required', 'integer', 'in:7,8,9'],
            'is_exam' => ['nullable', 'boolean'], 'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'maximum_attempt' => ['nullable', 'integer', 'min:1'], 'passing_score' => ['nullable', 'numeric'],
        ]);
        $section->assessments()->create([...$data, 'is_exam' => $request->boolean('is_exam'), 'maximum_attempt' => $data['maximum_attempt'] ?? 1]);

        return back()->with('status', 'Assessment dibuat.');
    }

    public function storeQuestion(Request $request, Assessment $assessment)
    {
        $this->authorize('manage', $assessment->section->course);
        $data = $request->validate([
            'type' => ['required', 'in:multiple_choice,true_false,essay,file_upload'],
            'prompt' => ['required'], 'points' => ['required', 'numeric', 'min:0'],
            'correct_answer' => ['nullable'], 'options' => ['nullable'],
        ]);
        $question = $assessment->questions()->create([...$data, 'position' => $assessment->questions()->count() + 1]);
        if ($data['type'] === 'multiple_choice') {
            foreach (preg_split('/\r\n|\r|\n/', $data['options'] ?? '') as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $question->options()->create(['option_text' => ltrim($line, '* '), 'is_correct' => str_starts_with($line, '*')]);
                }
            }
        }

        return back()->with('status', 'Soal ditambahkan.');
    }

    public function showStudent(Assessment $assessment, AssessmentService $service, ExamService $examService)
    {
        $student = auth()->user()->student;
        abort_unless($service->canStudentAccess($student, $assessment), 403);
        $session = $assessment->is_exam ? $examService->start($student, $assessment) : null;

        return view('student.assessment', [
            'assessment' => $assessment->load('questions.options'),
            'canAttempt' => $service->canStudentAttempt($student, $assessment),
            'examSession' => $session,
            'remainingSeconds' => $session ? $examService->remainingSeconds($session) : null,
        ]);
    }

    public function submit(Request $request, Assessment $assessment, AssessmentService $service)
    {
        $service->submit(auth()->user()->student, $assessment, ['answers' => $request->input('answers', [])]);

        return redirect()->route('student.dashboard')->with('status', 'Assessment berhasil dikirim.');
    }

    public function manualGrade(Request $request, Submission $submission, AssessmentService $service)
    {
        $this->authorize('view', $submission);
        $data = $request->validate(['score' => ['required', 'numeric'], 'feedback' => ['nullable']]);
        $service->manualGrade($submission, $data['score'], $data['feedback'] ?? null);

        return back()->with('status', 'Nilai disimpan.');
    }
}
