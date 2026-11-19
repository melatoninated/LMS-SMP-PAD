<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Database lama belum memiliki kolom ini; database baru dapat sudah
        // memilikinya jika migration dasar telah diperbarui.
        if (! Schema::hasColumn('exam_sessions', 'attempt_no')) {
            Schema::table('exam_sessions', function (Blueprint $table) {
                $table->unsignedInteger('attempt_no')->default(1)->after('student_id');
            });
        }

        // Buat index khusus agar foreign key assessment_id tetap didukung.
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->index(
                'assessment_id',
                'exam_sessions_assessment_id_index'
            );
        });

        // Setelah index khusus tersedia, unique index lama dapat diganti.
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropUnique(
                'exam_sessions_assessment_id_student_id_unique'
            );

            $table->unique(
                ['assessment_id', 'student_id', 'attempt_no'],
                'exam_sessions_assessment_student_attempt_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropUnique(
                'exam_sessions_assessment_student_attempt_unique'
            );

            $table->unique(
                ['assessment_id', 'student_id'],
                'exam_sessions_assessment_id_student_id_unique'
            );
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropIndex('exam_sessions_assessment_id_index');
        });
    }
};
