<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Course;
use App\Models\Material;
use App\Models\ProfilingQuestion;
use App\Services\AcademicYearService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentDashboardController extends Controller
{
    public function dashboard(AcademicYearService $years)
    {
        $student = auth()->user()->student;

        return view('student.dashboard', [
            'student' => $student->load('classRoom'),
            'expectedGrade' => $years->studentExpectedGrade($student),
            'classWarning' => $years->classUpdateRequired($student),
            'courses' => $student->courses()->get(),
        ]);
    }

    public function courses(AcademicYearService $years)
    {
        $grade = $years->studentExpectedGrade(auth()->user()->student);

        return view('student.courses', ['courses' => Course::where('is_active', true)->orderBy('target_grade')->get(), 'grade' => $grade]);
    }

    public function enroll(Course $course, AcademicYearService $years)
    {
        $student = auth()->user()->student;
        abort_unless($course->target_grade <= ($years->studentExpectedGrade($student) ?? 0), 403);
        $student->courses()->syncWithoutDetaching($course);

        return back()->with('status', 'Berhasil enroll mata pelajaran.');
    }

    public function showCourse(Course $course, AcademicYearService $years)
    {
        $student = auth()->user()->student;
        abort_unless($course->target_grade <= ($years->studentExpectedGrade($student) ?? 0), 403);

        return view('student.course', ['course' => $course->load('sections.materials', 'sections.assessments'), 'grade' => $years->studentExpectedGrade($student)]);
    }

    public function completeMaterial(Material $material, AcademicYearService $years)
    {
        $student = auth()->user()->student;
        abort_unless($material->target_grade <= ($years->studentExpectedGrade($student) ?? 0), 403);
        DB::table('material_progress')->updateOrInsert(
            ['material_id' => $material->id, 'student_id' => $student->id],
            ['completed_at' => now()]
        );

        return back()->with('status', 'Materi ditandai selesai.');
    }

    public function profile(AcademicYearService $years)
    {
        $student = auth()->user()->student;
        $grade = $years->studentExpectedGrade($student);

        return view('student.profile', [
            'classes' => ClassRoom::where('grade_level', $grade)->get(),
            'questions' => ProfilingQuestion::all(),
            'recommendations' => DB::table('profiling_answers')->join('profiling_questions', 'profiling_questions.id', '=', 'profiling_answers.profiling_question_id')->where('student_id', $student->id)->where('answer_value', '<=', 2)->pluck('material_keyword'),
        ]);
    }

    public function updateClass(Request $request)
    {
        auth()->user()->student->update($request->validate(['class_room_id' => ['required', 'exists:class_rooms,id']]));

        return back()->with('status', 'Kelas berhasil diperbarui.');
    }

    public function saveProfiling(Request $request)
    {
        foreach ($request->input('answers', []) as $questionId => $value) {
            DB::table('profiling_answers')->updateOrInsert(
                ['profiling_question_id' => $questionId, 'student_id' => auth()->user()->student->id],
                ['answer_value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        return back()->with('status', 'Profiling tersimpan.');
    }
}
