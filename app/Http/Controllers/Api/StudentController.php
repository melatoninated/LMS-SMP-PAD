<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentDraft;
use App\Models\ClassRoom;
use App\Models\Course;
use App\Models\ExamSession;
use App\Models\Material;
use App\Models\ProfilingQuestion;
use App\Services\AcademicYearService;
use App\Services\AssessmentService;
use App\Services\ExamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function dashboard(Request $request, AcademicYearService $years)
    {
        $student = $request->user()->student;

        return response()->json([
            'data' => [
                'student' => $student->load('classRoom'),
                'expected_grade' => $years->studentExpectedGrade($student),
                'class_update_required' => $years->classUpdateRequired($student),
                'courses' => Course::where('is_deleted', false)->where('is_active', true)->get()
                    ->filter(fn (Course $course) => app(AssessmentService::class)->canStudentViewCourse($student, $course))->values(),
            ],
        ]);
    }

    public function courses(Request $request, AcademicYearService $years)
    {
        $grade = $years->studentExpectedGrade($request->user()->student);

        return response()->json([
            'data' => Course::where('is_active', true)->where('is_deleted', false)->orderBy('target_grade')->get()
                ->filter(fn (Course $course) => app(AssessmentService::class)->canStudentViewCourse($request->user()->student, $course))->values(),
            'expected_grade' => $grade,
        ]);
    }

    public function enroll(Request $request, Course $course, AcademicYearService $years)
    {
        $student = $request->user()->student;

        abort_unless(app(AssessmentService::class)->canStudentViewCourse($student, $course), 403);

        abort_unless($course->is_active, 403, 'Mata pelajaran tidak aktif.');

        $student->courses()->syncWithoutDetaching($course);

        return response()->json([
            'message' => 'Berhasil enroll mata pelajaran.',
        ]);
    }

    public function showCourse(Request $request, Course $course, AcademicYearService $years)
    {
        $student = $request->user()->student;

        abort_unless(app(AssessmentService::class)->canStudentViewCourse($student, $course), 403);

        $course->load([
                'sections.materials',
                'sections.assessments' => fn ($query) => $query->where('is_published', true),
            ]);
        $course->setAttribute('is_read_only', $course->academic_year_id !== null
            && $course->academic_year_id !== $years->active()?->id);

        return response()->json([
            'data' => $course,
        ]);
    }

    public function completeMaterial(
        Request $request,
        Material $material,
        AcademicYearService $years
    ) {
        $student = $request->user()->student;

        abort_unless(app(AssessmentService::class)->canStudentViewCourse($student, $material->section->course), 403);

        DB::table('material_progress')->updateOrInsert(
            [
                'material_id' => $material->id,
                'student_id' => $student->id,
            ],
            [
                'completed_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Materi ditandai selesai.',
        ]);
    }

    public function showAssessment(
        Request $request,
        Assessment $assessment,
        AssessmentService $service,
        ExamService $exams
    ) {
        $student = $request->user()->student;

        abort_unless(
            $service->canStudentAccess($student, $assessment),
            403
        );

        $assessment->load('questions.options');

        // Jangan pernah mengirim kunci jawaban kepada siswa.
        foreach ($assessment->questions as $question) {
            $question->options->makeHidden('is_correct');
        }

        $canAttempt = $service->canStudentAttempt($student, $assessment);
        $session = $assessment->is_exam && $canAttempt
            ? $exams->start($student, $assessment)
            : null;

        return response()->json([
            'data' => [
                'assessment' => $assessment,
                'can_attempt' => $canAttempt,
                'exam_session' => $session,
                'remaining_seconds' => $session
                    ? $exams->remainingSeconds($session)
                    : null,
            ],
        ]);
    }

    public function submit(
        Request $request,
        Assessment $assessment,
        AssessmentService $service
    ) {
        $request->validate([
            'answers' => ['nullable', 'array'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:51200'],
        ]);

        $submission = $service->submit(
            $request->user()->student,
            $assessment,
            [
                'answers' => $request->input('answers', []),
                'files' => $request->file('files', []),
            ]
        );

        return response()->json([
            'data' => $submission,
        ], 201);
    }

    public function saveAssessmentDraft(Request $request, Assessment $assessment, AssessmentService $service)
    {
        abort_unless($service->canStudentAttempt($request->user()->student, $assessment), 403);
        $data = $request->validate(['answers' => ['required', 'array']]);
        AssessmentDraft::updateOrCreate(
            ['assessment_id' => $assessment->id, 'student_id' => $request->user()->student->id],
            ['answers' => $data['answers']]
        );
        return response()->noContent();
    }

    public function log(Request $request, ExamSession $examSession)
    {
        abort_unless(
            $examSession->student_id === $request->user()->student?->id,
            403
        );

        abort_unless(
            $examSession->status === 'active'
            && now()->lt($examSession->expires_at),
            403,
            'Sesi ujian sudah tidak aktif.'
        );

        $data = $request->validate([
            'event' => ['required', 'string', 'max:100'],
            'detail' => ['nullable', 'string'],
        ]);

        $examSession->logs()->create($data);

        return response()->noContent();
    }

    public function profile(Request $request, AcademicYearService $years)
    {
        $student = $request->user()->student;
        $grade = $years->studentExpectedGrade($student);

        return response()->json([
            'data' => [
                'classes' => ClassRoom::where('grade_level', $grade)
                    ->where('academic_year_id', $years->active()?->id)
                    ->where('is_active', true)->where('is_deleted', false)->get(),
                'questions' => ProfilingQuestion::all(),
                'recommendations' => DB::table('profiling_answers')
                    ->join(
                        'profiling_questions',
                        'profiling_questions.id',
                        '=',
                        'profiling_answers.profiling_question_id'
                    )
                    ->where('student_id', $student->id)
                    ->where('answer_value', '<=', 2)
                    ->pluck('material_keyword'),
            ],
        ]);
    }

    public function updateClass(Request $request, AcademicYearService $years)
    {
        $data = $request->validate([
            'class_room_id' => ['required', 'exists:class_rooms,id'],
        ]);

        $student = $request->user()->student;
        $class = ClassRoom::where('is_active', true)->where('is_deleted', false)->findOrFail($data['class_room_id']);
        abort_unless($class->academic_year_id === $years->active()?->id && $class->grade_level === $years->studentExpectedGrade($student), 422, 'Rombel harus sesuai tingkat dan tahun ajaran aktif.');
        DB::transaction(function () use ($student, $class, $years) {
            $student->update(['class_room_id' => $class->id]);
            $student->classHistories()->updateOrCreate(
                ['academic_year_id' => $years->active()->id],
                ['class_room_id' => $class->id]
            );
        });

        return response()->json([
            'data' => $request->user()->student->fresh('classRoom'),
        ]);
    }

    public function saveProfiling(Request $request)
    {
        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['integer', 'between:1,5'],
        ]);

        foreach ($data['answers'] as $questionId => $value) {
            DB::table('profiling_answers')->updateOrInsert(
                [
                    'profiling_question_id' => $questionId,
                    'student_id' => $request->user()->student->id,
                ],
                [
                    'answer_value' => $value,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return response()->json([
            'message' => 'Profiling tersimpan.',
        ]);
    }
}
