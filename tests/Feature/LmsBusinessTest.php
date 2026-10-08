<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\Student;
use App\Models\Submission;
use App\Models\User;
use App\Services\AcademicYearService;
use App\Services\AssessmentService;
use App\Services\ExamService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LmsBusinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_student_default_password_forces_change(): void
    {
        $user = User::where('username', '0012345671')->first();
        $this->assertTrue(Hash::check('2012-05-17', $user->password));
        $this->assertTrue($user->must_change_password);
    }

    public function test_admin_reset_password_uses_birth_date(): void
    {
        $admin = User::where('role', 'admin')->first();
        $student = Student::where('nisn', '0012345671')->first();
        $student->user->update(['password' => Hash::make('PasswordBaru!2026'), 'must_change_password' => false]);

        $this->actingAs($admin)->post(route('admin.students.reset-password', $student))->assertRedirect();

        $student->user->refresh();
        $this->assertTrue(Hash::check('2012-05-17', $student->user->password));
        $this->assertTrue($student->user->must_change_password);
    }

    public function test_teacher_cannot_manage_other_teacher_course(): void
    {
        $teacher = User::where('username', 'guru2@example.com')->first();
        $course = Course::where('name', 'Matematika')->first();

        $this->actingAs($teacher)->get(route('teacher.courses.show', $course))->assertForbidden();
    }

    public function test_grade_progression_from_academic_year(): void
    {
        $student = Student::where('nisn', '0012345671')->first();
        $service = app(AcademicYearService::class);
        $this->assertSame(7, $service->studentExpectedGrade($student));

        $service->activate(AcademicYear::where('start_year', 2027)->first());
        $this->assertSame(8, $service->studentExpectedGrade($student->fresh()));
        $this->assertTrue($service->classUpdateRequired($student->fresh()));
    }

    public function test_student_grade_eight_cannot_attempt_grade_seven_assessment(): void
    {
        $student = Student::where('nisn', '0012345673')->first();
        $assessment = Assessment::where('title', 'Kuis Persamaan Linear')->first();

        $this->assertFalse(app(AssessmentService::class)->canStudentAttempt($student, $assessment));
        $this->assertTrue(app(AssessmentService::class)->canStudentAccess($student, $assessment));
    }

    public function test_auto_and_manual_grading(): void
    {
        $student = Student::where('nisn', '0012345671')->first();
        $assessment = Assessment::where('title', 'Kuis Persamaan Linear')->first();
        $option = Question::where('type', 'multiple_choice')->first()->options()->where('is_correct', true)->first();

        $submission = app(AssessmentService::class)->submit($student, $assessment, ['answers' => [
            Question::where('type', 'multiple_choice')->first()->id => $option->id,
            Question::where('type', 'true_false')->first()->id => 'true',
            Question::where('type', 'essay')->first()->id => 'Essay',
        ]]);

        $this->assertSame('submitted', $submission->status);
        $this->assertEquals(75, $submission->answers()->sum('points_awarded'));
    }

    public function test_exam_timer_is_server_side(): void
    {
        $student = Student::where('nisn', '0012345671')->first();
        $exam = Assessment::where('title', 'UTS Matematika')->first();
        $session = app(ExamService::class)->start($student, $exam);
        $this->assertTrue($session->expires_at->greaterThan(now()));

        $session->update(['expires_at' => now()->subSecond()]);
        app(ExamService::class)->autoSubmitExpired();
        $this->assertSame('expired', $session->fresh()->status);
        $this->assertTrue(Submission::where('assessment_id', $exam->id)->where('student_id', $student->id)->exists());
    }

    public function test_parent_only_sees_related_child(): void
    {
        $parent = User::where('username', 'ortu-0012345671')->first();
        $own = Student::where('nisn', '0012345671')->first();
        $other = Student::where('nisn', '0012345672')->first();

        $this->assertTrue($parent->parentProfile->students()->whereKey($own->id)->exists());
        $this->assertFalse($parent->parentProfile->students()->whereKey($other->id)->exists());
    }
}
