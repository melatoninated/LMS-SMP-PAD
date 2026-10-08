<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = ['name', 'start_year', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function classRooms()
    {
        return $this->hasMany(ClassRoom::class);
    }

    public function studentClassHistories()
    {
        return $this->hasMany(StudentClassHistory::class);
    }
}