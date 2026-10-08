<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    protected $fillable = ['section_id', 'title', 'description', 'target_grade', 'is_exam', 'duration_minutes', 'start_at', 'end_at', 'maximum_attempt', 'passing_score'];

    protected function casts(): array
    {
        return ['is_exam' => 'boolean', 'start_at' => 'datetime', 'end_at' => 'datetime'];
    }

    public function section() { return $this->belongsTo(Section::class); }
    public function questions() { return $this->hasMany(Question::class)->orderBy('position'); }
}
