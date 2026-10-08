<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'user_id',
        'nisn',
        'birth_date',
        'cohort_year',
        'class_room_id',
        'is_active',
        'is_graduated',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
            'is_graduated' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function classHistories()
    {
        return $this->hasMany(StudentClassHistory::class);
    }

    public function parents()
    {
        return $this->belongsToMany(ParentProfile::class, 'parent_student');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_student')
            ->withTimestamps();
    }
}