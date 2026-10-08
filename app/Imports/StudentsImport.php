<?php

namespace App\Imports;

use App\Models\ClassRoom;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToArray, WithHeadingRow
{
    public int $imported = 0;
    public array $failures = [];

    public function array(array $rows): void
    {
        foreach ($rows as $index => $row) {
            $errors = [];
            if (blank($row['nama'] ?? null)) $errors[] = 'nama wajib';
            if (blank($row['nisn'] ?? null)) $errors[] = 'NISN wajib';
            if (blank($row['tanggal_lahir'] ?? null)) $errors[] = 'tanggal_lahir wajib';
            if (blank($row['angkatan'] ?? null)) $errors[] = 'angkatan wajib';
            if (Student::where('nisn', $row['nisn'] ?? null)->exists()) $errors[] = 'NISN sudah ada';

            $class = filled($row['kelas'] ?? null) ? ClassRoom::where('name', $row['kelas'])->first() : null;
            if (filled($row['kelas'] ?? null) && ! $class) $errors[] = 'kelas tidak ditemukan';

            if ($errors) {
                $this->failures[] = ['row' => $index + 2, 'errors' => $errors];
                continue;
            }

            DB::transaction(function () use ($row, $class) {
                $user = User::create(['name' => $row['nama'], 'username' => $row['nisn'], 'password' => Hash::make($row['tanggal_lahir']), 'role' => 'student', 'must_change_password' => true]);
                $student = Student::create(['user_id' => $user->id, 'nisn' => $row['nisn'], 'birth_date' => $row['tanggal_lahir'], 'cohort_year' => $row['angkatan'], 'class_room_id' => $class?->id]);
                if (filled($row['nama_orang_tua'] ?? null)) {
                    $parentUser = User::create(['name' => $row['nama_orang_tua'], 'username' => 'ortu-'.$row['nisn'], 'password' => Hash::make('OrtuDev!2026'), 'role' => 'parent']);
                    ParentProfile::create(['user_id' => $parentUser->id])->students()->attach($student);
                }
                $this->imported++;
            });
        }
    }
}
