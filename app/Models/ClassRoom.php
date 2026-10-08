<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassRoom extends Model
{
    protected $fillable = [
        'name',
        'grade_level',
        'is_active',
        'academic_year_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function studentClassHistories()
    {
        return $this->hasMany(StudentClassHistory::class);
    }
}