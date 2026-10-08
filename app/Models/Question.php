<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['assessment_id', 'type', 'prompt', 'points', 'correct_answer', 'position'];

    public function assessment() { return $this->belongsTo(Assessment::class); }
    public function options() { return $this->hasMany(QuestionOption::class); }
}
