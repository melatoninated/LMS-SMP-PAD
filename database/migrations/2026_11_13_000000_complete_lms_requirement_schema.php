<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Student and parent intentionally share the child's NISN, so username
        // uniqueness must be scoped to a role.
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_username_unique');
            $table->unique(['username', 'role'], 'users_username_role_unique');
            $table->boolean('is_deleted')->default(false)->index()->after('is_active');
        });

        foreach (['students', 'teachers', 'parents', 'class_rooms', 'academic_years', 'courses'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->boolean('is_deleted')->default(false)->index();
            });
        }

        Schema::table('parents', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->unique()->after('user_id')
                ->constrained('students')->nullOnDelete();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->boolean('is_deleted')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('teaching_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_room_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('grade_level');
            $table->boolean('is_deleted')->default(false)->index();
            $table->timestamps();
            $table->unique(
                ['teacher_id', 'subject_id', 'academic_year_id', 'class_room_id'],
                'teaching_assignment_unique'
            );
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('teaching_assignment_id')->nullable()->after('teacher_id')
                ->constrained('teaching_assignments')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->after('teaching_assignment_id')
                ->constrained('academic_years')->nullOnDelete();
            $table->foreignId('class_room_id')->nullable()->after('academic_year_id')
                ->constrained('class_rooms')->nullOnDelete();
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->string('assessment_type')->default('practice')->after('description');
            $table->boolean('is_published')->default(false)->after('is_exam');
            $table->boolean('require_fullscreen')->default(false)->after('is_published');
            $table->boolean('anti_copy_paste')->default(false)->after('require_fullscreen');
        });

        // Make any legacy parent profile point at its first related child. New
        // writes are enforced by the unique parents.student_id column.
        $assignedStudents = [];
        DB::table('parents')->orderBy('id')->each(function (object $parent) use (&$assignedStudents): void {
            $studentId = DB::table('parent_student')
                ->where('parent_profile_id', $parent->id)
                ->value('student_id');

            if ($studentId !== null && ! isset($assignedStudents[$studentId])) {
                DB::table('parents')->where('id', $parent->id)->update(['student_id' => $studentId]);
                $student = DB::table('students')->where('id', $studentId)->first();
                if ($student !== null) {
                    DB::table('users')->where('id', $parent->user_id)->update([
                        'username' => $student->nisn,
                        'password' => Hash::make(\Carbon\Carbon::parse($student->birth_date)->format('Ymd')),
                        'must_change_password' => true,
                        'updated_at' => now(),
                    ]);
                }
                $assignedStudents[$studentId] = true;
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['teaching_assignment_id']);
            $table->dropForeign(['academic_year_id']);
            $table->dropForeign(['class_room_id']);
            $table->dropColumn(['teaching_assignment_id', 'academic_year_id', 'class_room_id']);
        });
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn(['assessment_type', 'is_published', 'require_fullscreen', 'anti_copy_paste']);
        });
        Schema::dropIfExists('teaching_assignments');
        Schema::dropIfExists('subjects');
        Schema::table('parents', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropUnique('parents_student_id_unique');
            $table->dropColumn('student_id');
        });
        foreach (['students', 'teachers', 'parents', 'class_rooms', 'academic_years', 'courses'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropIndex(['is_deleted']);
                $table->dropColumn('is_deleted');
            });
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_username_role_unique');
            $table->unique('username');
            $table->dropIndex(['is_deleted']);
            $table->dropColumn('is_deleted');
        });
    }
};
