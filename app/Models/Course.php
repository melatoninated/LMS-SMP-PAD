<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = ['teacher_id', 'name', 'description', 'target_grade', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function teacher() { return $this->belongsTo(Teacher::class); }
    public function sections() { return $this->hasMany(Section::class)->orderBy('position'); }
    public function students() { return $this->belongsToMany(Student::class, 'course_student')->withTimestamps(); }
}
