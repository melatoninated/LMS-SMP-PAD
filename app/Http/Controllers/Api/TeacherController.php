<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Section;
use App\Models\Submission;
use App\Models\TeachingAssignment;
use App\Services\AssessmentService;
use App\Services\UploadPolicy;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TeacherController extends Controller
{
    public function dashboard(Request $request)
    {
        $teacher = $request->user()->teacher;

        return response()->json([
            'data' => [
                'courses' => $teacher->courses()->where('is_deleted', false)->withCount('students')->get(),
                'need_grading' => Submission::whereHas(
                    'assessment.section.course',
                    fn ($q) => $q->where('teacher_id', $teacher->id)
                )
                    ->where('status', 'submitted')
                    ->count(),
                'alerts' => [
                    'submissions_need_grading' => Submission::whereHas(
                        'assessment.section.course', fn ($q) => $q->where('teacher_id', $teacher->id)
                    )->where('status', 'submitted')->count(),
                ],
            ],
        ]);
    }

    public function courses(Request $request)
    {
        return response()->json([
            'data' => $request->user()->teacher
                ->courses()->where('is_deleted', false)
                ->withCount('students')
                ->get(),
        ]);
    }

    public function storeCourse(Request $request)
    {
        $data = $request->validate([
            'teaching_assignment_id' => ['required', 'exists:teaching_assignments,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);
        $assignment = TeachingAssignment::where('is_deleted', false)
            ->where('teacher_id', $request->user()->teacher->id)
            ->with('subject')
            ->findOrFail($data['teaching_assignment_id']);
        $course = $request->user()->teacher->courses()->create([
            'teaching_assignment_id' => $assignment->id,
            'academic_year_id' => $assignment->academic_year_id,
            'class_room_id' => $assignment->class_room_id,
            'name' => $data['name'] ?? $assignment->subject->name,
            'description' => $data['description'] ?? null,
            'target_grade' => $assignment->grade_level,
        ]);

        return response()->json([
            'data' => $course,
        ], 201);
    }

    public function showCourse(Course $course)
    {
        $this->authorize('manage', $course);

        return response()->json([
            'data' => $course->load(
                'sections.materials',
                'sections.assessments.questions.options'
            ),
        ]);
    }

    public function updateCourse(Request $request, Course $course)
    {
        $this->authorize('manage', $course);

        $course->update(
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'is_active' => ['sometimes', 'boolean'],
            ])
        );

        return response()->json([
            'data' => $course->fresh(),
        ]);
    }

    public function destroyCourse(Course $course)
    {
        $this->authorize('manage', $course);

        $course->update(['is_deleted' => true, 'is_active' => false]);

        return response()->noContent();
    }

    public function storeSection(Request $request, Course $course)
    {
        $this->authorize('manage', $course);

        $section = $course->sections()->create(
            $request->validate([
                'title' => ['required', 'string', 'max:255'],
            ]) + [
                'position' => $course->sections()->count() + 1,
            ]
        );

        return response()->json([
            'data' => $section,
        ], 201);
    }

    public function storeMaterial(Request $request, Section $section, UploadPolicy $uploads)
    {
        $this->authorize('manage', $section->course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:text,pdf,video,audio,file'],
            'body' => ['nullable', 'string'],
            'file_path' => ['nullable', 'string', 'max:2048'],
            'file' => ['nullable', 'file', 'max:51200'],
            'target_grade' => ['required', 'integer', 'in:7,8,9'],
        ]);

        if ($request->hasFile('file')) {
            $category = $uploads->validate($request->file('file'));
            if (($data['type'] === 'pdf' && $category !== 'pdf')
                || ($data['type'] === 'audio' && $category !== 'audio')
                || ($data['type'] === 'video' && $category !== 'video')) {
                throw ValidationException::withMessages(['file' => ['Jenis file tidak sesuai dengan tipe materi.']]);
            }
            $data['file_path'] = $request->file('file')->store('materials', 'public');
        }

        if ($data['type'] !== 'text' && empty($data['file_path'])) {
            throw ValidationException::withMessages(['file' => ['Materi non-teks wajib memiliki file atau URL file.']]);
        }

        return response()->json([
            'data' => Material::create($data + [
                'section_id' => $section->id,
            ]),
        ], 201);
    }

    public function storeAssessment(Request $request, Section $section)
    {
        $this->authorize('manage', $section->course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assessment_type' => ['required', 'in:practice,midterm,final,daily_exam'],
            'target_grade' => ['required', 'integer', 'in:7,8,9'],
            'is_exam' => ['nullable', 'boolean'],
            'is_published' => ['required', 'boolean'],
            'require_fullscreen' => ['sometimes', 'boolean'],
            'anti_copy_paste' => ['sometimes', 'boolean'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['required', 'date', 'after:now', 'after:start_at'],
            'maximum_attempt' => ['nullable', 'integer', 'min:1'],
            'passing_score' => ['nullable', 'numeric', 'min:0'],
        ]);

        $isExam = $request->boolean('is_exam');

        $assessment = $section->assessments()->create($data + [
            'is_exam' => $isExam,
            'maximum_attempt' => $data['maximum_attempt'] ?? 1,
        ]);

        return response()->json([
            'data' => $assessment,
        ], 201);
    }

    public function storeQuestion(Request $request, Assessment $assessment)
    {
        $this->authorize('manage', $assessment->section->course);

        $data = $request->validate([
            'type' => [
                'required',
                'in:multiple_choice,true_false,essay,file_upload',
            ],
            'prompt' => ['required', 'string'],
            'points' => ['required', 'numeric', 'min:0', 'max:100'],
            'correct_answer' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'options.*.text' => ['required_with:options', 'string'],
            'options.*.is_correct' => ['sometimes', 'boolean'],
        ]);

        if ($data['type'] === 'multiple_choice') {
            $correctOptions = collect($data['options'] ?? [])->filter(fn (array $option) => (bool) ($option['is_correct'] ?? false));
            if (count($data['options'] ?? []) < 2 || $correctOptions->count() !== 1) {
                throw ValidationException::withMessages(['options' => ['Pilihan ganda wajib memiliki minimal dua opsi dan tepat satu jawaban benar.']]);
            }
        }
        if ($data['type'] === 'true_false' && ! in_array(strtolower((string) ($data['correct_answer'] ?? '')), ['true', 'false'], true)) {
            throw ValidationException::withMessages(['correct_answer' => ['Kunci jawaban benar/salah wajib true atau false.']]);
        }

        $question = $assessment->questions()->create([
            'type' => $data['type'],
            'prompt' => $data['prompt'],
            'points' => $data['points'],
            'correct_answer' => $data['correct_answer'] ?? null,
            'position' => $assessment->questions()->count() + 1,
        ]);

        foreach ($data['options'] ?? [] as $option) {
            $question->options()->create([
                'option_text' => $option['text'],
                'is_correct' => $option['is_correct'] ?? false,
            ]);
        }

        return response()->json([
            'data' => $question->load('options'),
        ], 201);
    }

    public function submissions(Request $request)
    {
        $teacherId = $request->user()->teacher->id;

        return response()->json([
            'data' => Submission::with(
                'student.user',
                'assessment.section.course',
                'answers.question'
            )
                ->whereHas(
                    'assessment.section.course',
                    fn ($q) => $q->where('teacher_id', $teacherId)
                )
                ->latest()
                ->get(),
        ]);
    }

    public function grade(
        Request $request,
        Submission $submission,
        AssessmentService $service
    ) {
        $this->authorize('view', $submission);

        $data = $request->validate([
            'score' => ['required', 'numeric', 'between:0,100'],
            'feedback' => ['nullable', 'string'],
        ]);

        $submission->loadMissing('assessment.questions');

        $maximumScore = (float) $submission->assessment
            ->questions
            ->sum('points');

        if ((float) $data['score'] > min(100, $maximumScore)) {
            throw ValidationException::withMessages([
                'score' => [
                    'Nilai tidak boleh melebihi 100 atau total poin soal.',
                ],
            ]);
        }

        $service->manualGrade(
            $submission,
            (float) $data['score'],
            $data['feedback'] ?? null
        );

        return response()->json([
            'data' => $submission->fresh(),
        ]);
    }
}
