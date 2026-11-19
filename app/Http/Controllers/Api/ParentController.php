<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Submission;
use App\Services\AssessmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParentController extends Controller
{
    public function dashboard(Request $request)
    {
        $student = $request->user()->parentProfile->student()->with('user', 'classRoom')->firstOrFail();
        $courses = Course::where('is_active', true)->where('is_deleted', false)->get()
            ->filter(fn (Course $course) => app(AssessmentService::class)->canStudentViewCourse($student, $course))
            ->map(function (Course $course) use ($student) {
                $materialIds = $course->sections()->with('materials:id,section_id')->get()
                    ->flatMap(fn ($section) => $section->materials->pluck('id'));
                $completed = $materialIds->isEmpty() ? 0 : DB::table('material_progress')
                    ->where('student_id', $student->id)->whereIn('material_id', $materialIds)->count();
                return [
                    'course' => $course,
                    'materials_total' => $materialIds->count(),
                    'materials_completed' => $completed,
                    'progress_percent' => $materialIds->isEmpty() ? 0 : round($completed / $materialIds->count() * 100, 2),
                ];
            })->values();
        $submissions = Submission::where('student_id', $student->id)
            ->with('assessment.section.course')->latest()->get();

        return response()->json(['data' => [
            'student' => $student,
            'course_progress' => $courses,
            'submissions' => $submissions,
            'alerts' => ['pending_grading' => $submissions->where('status', 'submitted')->count()],
        ]]);
    }
}
