<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function view(User $user, Submission $submission): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'student') {
            return $user->student?->id === $submission->student_id;
        }

        if ($user->role === 'parent') {
            return $user->parentProfile?->students()->whereKey($submission->student_id)->exists() ?? false;
        }

        if ($user->role === 'teacher') {
            return $submission->assessment->section->course->teacher_id === $user->teacher?->id;
        }

        return false;
    }
}
