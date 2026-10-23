<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('teachers')
            ->whereNotNull('employee_no')
            ->orderBy('id')
            ->each(function (object $teacher): void {
                DB::table('users')
                    ->where('id', $teacher->user_id)
                    ->where('role', 'teacher')
                    ->update(['username' => $teacher->employee_no, 'updated_at' => now()]);
            });

        DB::table('students')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->where('users.must_change_password', true)
            ->select('students.birth_date', 'users.id')
            ->orderBy('users.id')
            ->each(function (object $student): void {
                DB::table('users')->where('id', $student->id)->update([
                    'password' => Hash::make(Carbon::parse($student->birth_date)->format('dmY')),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        // Previous email-based teacher usernames cannot be reconstructed reliably.
        // Student passwords are intentionally not restored for security reasons.
    }
};
