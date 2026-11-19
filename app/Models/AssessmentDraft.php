<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentDraft extends Model
{
    protected $fillable = ['assessment_id', 'student_id', 'answers'];

    protected function casts(): array
    {
        return ['answers' => 'array'];
    }
}
