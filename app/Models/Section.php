<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['course_id', 'title', 'position'];

    public function course() { return $this->belongsTo(Course::class); }
    public function materials() { return $this->hasMany(Material::class); }
    public function assessments() { return $this->hasMany(Assessment::class); }
}
