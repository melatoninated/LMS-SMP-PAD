<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable = ['submission_id', 'question_id', 'answer_text', 'file_path', 'is_correct', 'points_awarded'];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }
}
