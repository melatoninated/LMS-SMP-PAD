<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    protected $fillable = [
        'section_id', 'title', 'description', 'assessment_type', 'target_grade',
        'is_exam', 'is_published', 'require_fullscreen', 'anti_copy_paste',
        'duration_minutes', 'start_at', 'end_at', 'maximum_attempt', 'passing_score',
    ];

    protected function casts(): array
    {
        return [
            'is_exam' => 'boolean', 'is_published' => 'boolean',
            'require_fullscreen' => 'boolean', 'anti_copy_paste' => 'boolean',
            'start_at' => 'datetime', 'end_at' => 'datetime',
        ];
    }

    public function section() { return $this->belongsTo(Section::class); }
    public function questions() { return $this->hasMany(Question::class)->orderBy('position'); }
}
