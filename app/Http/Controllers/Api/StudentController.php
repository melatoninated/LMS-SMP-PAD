<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
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
    public function dashboard(Request $request, AcademicYearService $years) { $student = $request->user()->student; return response()->json(['data' => ['student' => $student->load('classRoom'), 'expected_grade' => $years->studentExpectedGrade($student), 'class_update_required' => $years->classUpdateRequired($student), 'courses' => $student->courses()->get()]]); }
    public function courses(Request $request, AcademicYearService $years) { $grade = $years->studentExpectedGrade($request->user()->student); return response()->json(['data' => Course::where('is_active', true)->orderBy('target_grade')->get(), 'expected_grade' => $grade]); }
    public function enroll(Request $request, Course $course, AcademicYearService $years) { $student = $request->user()->student; abort_unless($course->target_grade <= ($years->studentExpectedGrade($student) ?? 0), 403); $student->courses()->syncWithoutDetaching($course); return response()->json(['message' => 'Berhasil enroll mata pelajaran.']); }
    public function showCourse(Request $request, Course $course, AcademicYearService $years) { $student = $request->user()->student; abort_unless($course->target_grade <= ($years->studentExpectedGrade($student) ?? 0), 403); return response()->json(['data' => $course->load('sections.materials', 'sections.assessments')]); }
    public function completeMaterial(Request $request, Material $material, AcademicYearService $years) { $student = $request->user()->student; abort_unless($material->target_grade <= ($years->studentExpectedGrade($student) ?? 0), 403); DB::table('material_progress')->updateOrInsert(['material_id' => $material->id, 'student_id' => $student->id], ['completed_at' => now()]); return response()->json(['message' => 'Materi ditandai selesai.']); }
    public function showAssessment(Request $request, Assessment $assessment, AssessmentService $service, ExamService $exams) { $student = $request->user()->student; abort_unless($service->canStudentAccess($student, $assessment), 403); $session = $assessment->is_exam ? $exams->start($student, $assessment) : null; return response()->json(['data' => ['assessment' => $assessment->load('questions.options'), 'can_attempt' => $service->canStudentAttempt($student, $assessment), 'exam_session' => $session, 'remaining_seconds' => $session ? $exams->remainingSeconds($session) : null]]); }
    public function submit(Request $request, Assessment $assessment, AssessmentService $service) { $request->validate(['answers' => ['nullable', 'array']]); $submission = $service->submit($request->user()->student, $assessment, ['answers' => $request->input('answers', [])]); return response()->json(['data' => $submission], 201); }
    public function log(Request $request, ExamSession $examSession) { abort_unless($examSession->student_id === $request->user()->student?->id, 403); $data = $request->validate(['event' => ['required', 'string', 'max:100'], 'detail' => ['nullable', 'string']]); $examSession->logs()->create($data); return response()->noContent(); }
    public function profile(Request $request, AcademicYearService $years) { $student = $request->user()->student; $grade = $years->studentExpectedGrade($student); return response()->json(['data' => ['classes' => ClassRoom::where('grade_level', $grade)->get(), 'questions' => ProfilingQuestion::all(), 'recommendations' => DB::table('profiling_answers')->join('profiling_questions', 'profiling_questions.id', '=', 'profiling_answers.profiling_question_id')->where('student_id', $student->id)->where('answer_value', '<=', 2)->pluck('material_keyword')]]); }
    public function updateClass(Request $request) { $data = $request->validate(['class_room_id' => ['required', 'exists:class_rooms,id']]); $request->user()->student->update($data); return response()->json(['data' => $request->user()->student->fresh('classRoom')]); }
    public function saveProfiling(Request $request) { $data = $request->validate(['answers' => ['required', 'array'], 'answers.*' => ['integer', 'between:1,5']]); foreach ($data['answers'] as $questionId => $value) DB::table('profiling_answers')->updateOrInsert(['profiling_question_id' => $questionId, 'student_id' => $request->user()->student->id], ['answer_value' => $value, 'updated_at' => now(), 'created_at' => now()]); return response()->json(['message' => 'Profiling tersimpan.']); }
}
