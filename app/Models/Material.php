<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = ['section_id', 'title', 'type', 'body', 'file_path', 'target_grade'];

    public function section() { return $this->belongsTo(Section::class); }
}
