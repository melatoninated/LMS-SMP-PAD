<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    protected $fillable = ['assessment_id', 'student_id', 'started_at', 'expires_at', 'submitted_at', 'status'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'expires_at' => 'datetime', 'submitted_at' => 'datetime'];
    }

    public function assessment() { return $this->belongsTo(Assessment::class); }
    public function student() { return $this->belongsTo(Student::class); }
    public function logs() { return $this->hasMany(ExamActivityLog::class); }
}
