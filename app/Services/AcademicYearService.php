<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AcademicYearService
{
    public function active(): ?AcademicYear
    {
        return AcademicYear::where('is_active', true)->first();
    }

    public function expectedGrade(int $cohortYear, ?AcademicYear $year = null): ?int
    {
        $year ??= $this->active();
        if (! $year) {
            return null;
        }

        $grade = 7 + ($year->start_year - $cohortYear);

        return $grade >= 7 && $grade <= 9 ? $grade : null;
    }

    public function studentExpectedGrade(Student $student): ?int
    {
        return $this->expectedGrade($student->cohort_year);
    }

    public function classUpdateRequired(Student $student): bool
    {
        $expected = $this->studentExpectedGrade($student);

        return $expected !== null && $student->classRoom?->grade_level !== $expected;
    }

    public function activate(AcademicYear $academicYear): void
    {
        DB::transaction(function () use ($academicYear) {
            AcademicYear::query()->update(['is_active' => false]);
            $academicYear->update(['is_active' => true]);

            Student::query()->each(function (Student $student) use ($academicYear) {
                $grade = $this->expectedGrade($student->cohort_year, $academicYear);
                $student->update([
                    'is_graduated' => $grade === null,
                    'is_active' => $grade !== null,
                ]);
            });

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'academic_year_activated',
                'entity_type' => AcademicYear::class,
                'entity_id' => $academicYear->id,
                'detail' => $academicYear->name,
                'created_at' => now(),
            ]);
        });
    }
}
