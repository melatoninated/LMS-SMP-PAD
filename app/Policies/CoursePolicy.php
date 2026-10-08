<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function manage(User $user, Course $course): bool
    {
        return $user->role === 'admin' || ($user->role === 'teacher' && $user->teacher?->id === $course->teacher_id);
    }
}
