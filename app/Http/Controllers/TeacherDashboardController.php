<?php

namespace App\Http\Controllers;

use App\Exports\GradesExport;
use App\Models\Submission;
use Maatwebsite\Excel\Facades\Excel;

class TeacherDashboardController extends Controller
{
    public function dashboard()
    {
        $teacher = auth()->user()->teacher;

        return view('teacher.dashboard', [
            'courses' => $teacher->courses()->withCount('students')->get(),
            'needGrading' => Submission::whereHas('assessment.section.course', fn ($q) => $q->where('teacher_id', $teacher->id))->where('status', 'submitted')->count(),
        ]);
    }

    public function grades()
    {
        $teacher = auth()->user()->teacher;

        return view('teacher.grades', [
            'submissions' => Submission::with('student.user', 'assessment.section.course')
                ->whereHas('assessment.section.course', fn ($q) => $q->where('teacher_id', $teacher->id))
                ->latest()
                ->get(),
        ]);
    }

    public function exportGrades()
    {
        return Excel::download(new GradesExport(auth()->user()->teacher), 'rekap_nilai.xlsx');
    }
}
