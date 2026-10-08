<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Section;
use App\Models\Submission;
use App\Services\AssessmentService;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function dashboard(Request $request) { $teacher = $request->user()->teacher; return response()->json(['data' => ['courses' => $teacher->courses()->withCount('students')->get(), 'need_grading' => Submission::whereHas('assessment.section.course', fn ($q) => $q->where('teacher_id', $teacher->id))->where('status', 'submitted')->count()]]); }
    public function courses(Request $request) { return response()->json(['data' => $request->user()->teacher->courses()->withCount('students')->get()]); }
    public function storeCourse(Request $request) { $course = $request->user()->teacher->courses()->create($request->validate(['name' => ['required'], 'description' => ['nullable'], 'target_grade' => ['required', 'integer', 'in:7,8,9']])); return response()->json(['data' => $course], 201); }
    public function showCourse(Course $course) { $this->authorize('manage', $course); return response()->json(['data' => $course->load('sections.materials', 'sections.assessments.questions.options')]); }
    public function updateCourse(Request $request, Course $course) { $this->authorize('manage', $course); $course->update($request->validate(['name' => ['required'], 'description' => ['nullable'], 'target_grade' => ['required', 'integer', 'in:7,8,9'], 'is_active' => ['sometimes', 'boolean']])); return response()->json(['data' => $course->fresh()]); }
    public function destroyCourse(Course $course) { $this->authorize('manage', $course); $course->delete(); return response()->noContent(); }
    public function storeSection(Request $request, Course $course) { $this->authorize('manage', $course); $section = $course->sections()->create($request->validate(['title' => ['required']]) + ['position' => $course->sections()->count() + 1]); return response()->json(['data' => $section], 201); }
    public function storeMaterial(Request $request, Section $section) { $this->authorize('manage', $section->course); $data = $request->validate(['title' => ['required'], 'type' => ['required', 'in:text,pdf,video,audio,file'], 'body' => ['nullable'], 'file_path' => ['nullable'], 'target_grade' => ['required', 'integer', 'in:7,8,9']]); return response()->json(['data' => Material::create($data + ['section_id' => $section->id])], 201); }
    public function storeAssessment(Request $request, Section $section) { $this->authorize('manage', $section->course); $data = $request->validate(['title' => ['required'], 'description' => ['nullable'], 'target_grade' => ['required', 'integer', 'in:7,8,9'], 'is_exam' => ['nullable', 'boolean'], 'duration_minutes' => ['nullable', 'integer', 'min:1'], 'maximum_attempt' => ['nullable', 'integer', 'min:1'], 'passing_score' => ['nullable', 'numeric']]); return response()->json(['data' => $section->assessments()->create($data + ['is_exam' => $request->boolean('is_exam'), 'maximum_attempt' => $data['maximum_attempt'] ?? 1])], 201); }
    public function storeQuestion(Request $request, Assessment $assessment) { $this->authorize('manage', $assessment->section->course); $data = $request->validate(['type' => ['required', 'in:multiple_choice,true_false,essay,file_upload'], 'prompt' => ['required'], 'points' => ['required', 'numeric', 'min:0'], 'correct_answer' => ['nullable'], 'options' => ['nullable', 'array'], 'options.*.text' => ['required_with:options'], 'options.*.is_correct' => ['boolean']]); $question = $assessment->questions()->create($data + ['position' => $assessment->questions()->count() + 1]); foreach ($data['options'] ?? [] as $option) $question->options()->create(['option_text' => $option['text'], 'is_correct' => $option['is_correct'] ?? false]); return response()->json(['data' => $question->load('options')], 201); }
    public function submissions(Request $request) { return response()->json(['data' => Submission::with('student.user', 'assessment.section.course', 'answers.question')->whereHas('assessment.section.course', fn ($q) => $q->where('teacher_id', $request->user()->teacher->id))->latest()->get()]); }
    public function grade(Request $request, Submission $submission, AssessmentService $service) { $this->authorize('view', $submission); $data = $request->validate(['score' => ['required', 'numeric'], 'feedback' => ['nullable']]); $service->manualGrade($submission, $data['score'], $data['feedback'] ?? null); return response()->json(['data' => $submission->fresh()]); }
}
