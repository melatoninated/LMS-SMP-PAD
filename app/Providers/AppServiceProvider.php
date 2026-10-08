<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\Submission;
use App\Policies\CoursePolicy;
use App\Policies\SubmissionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Submission::class, SubmissionPolicy::class);
    }
}
