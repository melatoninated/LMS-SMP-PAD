<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $fillable = ['assessment_id', 'student_id', 'attempt_no', 'submitted_at', 'score', 'status', 'feedback'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function assessment() { return $this->belongsTo(Assessment::class); }
    public function student() { return $this->belongsTo(Student::class); }
    public function answers() { return $this->hasMany(Answer::class); }
}
