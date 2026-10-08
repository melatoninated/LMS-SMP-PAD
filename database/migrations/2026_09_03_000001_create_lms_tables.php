<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('start_year')->unique();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('class_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedTinyInteger('grade_level')->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employee_no')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('parents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('nisn')->unique();
            $table->date('birth_date');
            $table->unsignedSmallInteger('cohort_year')->index();
            $table->foreignId('class_room_id')->nullable()->constrained('class_rooms')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_graduated')->default(false);
            $table->timestamps();
        });

        Schema::create('parent_student', function (Blueprint $table) {
            $table->foreignId('parent_profile_id')->constrained('parents')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('relationship')->default('Orang Tua');
            $table->primary(['parent_profile_id', 'student_id']);
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('target_grade')->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('course_student', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['course_id', 'student_id']);
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['text', 'pdf', 'video', 'audio', 'file']);
            $table->longText('body')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedTinyInteger('target_grade')->index();
            $table->timestamps();
        });

        Schema::create('material_progress', function (Blueprint $table) {
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->primary(['material_id', 'student_id']);
        });

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('target_grade')->index();
            $table->boolean('is_exam')->default(false);
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->unsignedInteger('maximum_attempt')->default(1);
            $table->decimal('passing_score', 5, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['multiple_choice', 'true_false', 'essay', 'file_upload']);
            $table->text('prompt');
            $table->decimal('points', 8, 2)->default(1);
            $table->text('correct_answer')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('option_text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('attempt_no')->default(1);
            $table->dateTime('submitted_at')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->enum('status', ['in_progress', 'submitted', 'graded', 'auto_submitted'])->default('in_progress');
            $table->text('feedback')->nullable();
            $table->unique(['assessment_id', 'student_id', 'attempt_no']);
            $table->timestamps();
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('answer_text')->nullable();
            $table->string('file_path')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_awarded', 8, 2)->nullable();
            $table->unique(['submission_id', 'question_id']);
            $table->timestamps();
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('score', 8, 2);
            $table->string('status')->default('final');
            $table->text('feedback')->nullable();
            $table->timestamp('graded_at');
            $table->timestamps();
        });

        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('expires_at')->index();
            $table->dateTime('submitted_at')->nullable();
            $table->enum('status', ['active', 'submitted', 'expired'])->default('active');
            $table->unique(['assessment_id', 'student_id']);
            $table->timestamps();
        });

        Schema::create('exam_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->text('detail')->nullable();
            $table->timestamps();
        });

        Schema::create('profiling_questions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->text('prompt');
            $table->string('material_keyword');
            $table->timestamps();
        });

        Schema::create('profiling_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profiling_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('answer_value');
            $table->unique(['profiling_question_id', 'student_id']);
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event')->index();
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('detail')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        foreach ([
            'audit_logs', 'profiling_answers', 'profiling_questions', 'exam_activity_logs', 'exam_sessions',
            'grades', 'answers', 'submissions', 'question_options', 'questions', 'assessments',
            'material_progress', 'materials', 'sections', 'course_student', 'courses', 'parent_student',
            'students', 'parents', 'teachers', 'class_rooms', 'academic_years',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
