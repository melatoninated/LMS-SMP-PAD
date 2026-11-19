<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = ['user_id', 'employee_no', 'phone', 'is_deleted'];

    public function user() { return $this->belongsTo(User::class); }
    public function courses() { return $this->hasMany(Course::class); }
    public function teachingAssignments() { return $this->hasMany(TeachingAssignment::class); }
}
