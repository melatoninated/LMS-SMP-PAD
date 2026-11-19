<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'teacher_id', 'teaching_assignment_id', 'academic_year_id', 'class_room_id',
        'name', 'description', 'target_grade', 'is_active', 'is_deleted',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_deleted' => 'boolean'];
    }

    public function teacher() { return $this->belongsTo(Teacher::class); }
    public function sections() { return $this->hasMany(Section::class)->orderBy('position'); }
    public function students() { return $this->belongsToMany(Student::class, 'course_student')->withTimestamps(); }
    public function teachingAssignment() { return $this->belongsTo(TeachingAssignment::class); }
    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
    public function classRoom() { return $this->belongsTo(ClassRoom::class); }
}
