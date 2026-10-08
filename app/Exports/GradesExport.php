<?php

namespace App\Exports;

use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GradesExport implements FromCollection, WithHeadings
{
    public function __construct(private Teacher $teacher) {}

    public function headings(): array
    {
        return ['Nama siswa', 'NISN', 'Kelas', 'Mata pelajaran', 'Assessment', 'Nilai', 'Status'];
    }

    public function collection()
    {
        return DB::table('submissions')
            ->join('students', 'students.id', '=', 'submissions.student_id')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->leftJoin('class_rooms', 'class_rooms.id', '=', 'students.class_room_id')
            ->join('assessments', 'assessments.id', '=', 'submissions.assessment_id')
            ->join('sections', 'sections.id', '=', 'assessments.section_id')
            ->join('courses', 'courses.id', '=', 'sections.course_id')
            ->where('courses.teacher_id', $this->teacher->id)
            ->select('users.name', 'students.nisn', 'class_rooms.name as class_name', 'courses.name as course_name', 'assessments.title', 'submissions.score', 'submissions.status')
            ->get();
    }
}
