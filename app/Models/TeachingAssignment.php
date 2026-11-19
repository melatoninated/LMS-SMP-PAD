<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeachingAssignment extends Model
{
    protected $fillable = [
        'teacher_id', 'subject_id', 'academic_year_id', 'class_room_id',
        'grade_level', 'is_deleted',
    ];

    protected function casts(): array
    {
        return ['is_deleted' => 'boolean'];
    }

    public function teacher() { return $this->belongsTo(Teacher::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
    public function classRoom() { return $this->belongsTo(ClassRoom::class); }
    public function courses() { return $this->hasMany(Course::class); }
}
