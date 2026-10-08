<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\ClassRoom;
use App\Models\Course;
use App\Models\ParentProfile;
use App\Models\ProfilingQuestion;
use App\Models\Question;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // =========================
        // ADMIN
        // =========================
        User::firstOrCreate(
            ['username' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'email' => 'admin@example.com',
                'password' => Hash::make('AdminDev!2026'),
                'role' => 'admin',
            ]
        );

        // =========================
        // TAHUN AJARAN
        // =========================
        $academicYear = AcademicYear::firstOrCreate(
            ['name' => '2026/2027'],
            [
                'start_year' => 2026,
                'is_active' => true,
            ]
        );

        AcademicYear::firstOrCreate(
            ['name' => '2027/2028'],
            [
                'start_year' => 2027,
                'is_active' => false,
            ]
        );

        AcademicYear::firstOrCreate(
            ['name' => '2028/2029'],
            [
                'start_year' => 2028,
                'is_active' => false,
            ]
        );

        // =========================
        // KELAS
        // =========================
        foreach ([7, 8, 9] as $level) {
            foreach (['A', 'B', 'C'] as $suffix) {
                ClassRoom::firstOrCreate(
                    [
                        'academic_year_id' => $academicYear->id,
                        'name' => $level . $suffix,
                    ],
                    [
                        'grade_level' => $level,
                        'is_active' => true,
                    ]
                );
            }
        }

        // =========================
        // GURU
        // =========================
        $teacherUsers = collect([
            ['guru1@example.com', 'Bu Ratna'],
            ['guru2@example.com', 'Pak Dimas'],
        ])->map(
            fn ($row) => User::firstOrCreate(
                ['username' => $row[0]],
                [
                    'name' => $row[1],
                    'email' => $row[0],
                    'password' => Hash::make('GuruDev!2026'),
                    'role' => 'teacher',
                ]
            )
        );

        $teachers = $teacherUsers->map(
            fn ($user, $i) => Teacher::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_no' => 'G' . str_pad(
                        $i + 1,
                        3,
                        '0',
                        STR_PAD_LEFT
                    ),
                ]
            )
        );

        // =========================
        // DATA SISWA
        // =========================
        $students = [
            ['0012345671', 'Budi Santoso', '2012-05-17', 2026, '7A'],
            ['0012345672', 'Siti Aminah', '2012-07-09', 2026, '7B'],
            ['0012345673', 'Andi Pratama', '2011-03-12', 2025, '8A'],
            ['0012345674', 'Dewi Lestari', '2011-10-21', 2025, '8B'],
            ['0012345675', 'Raka Wijaya', '2010-01-30', 2024, '9A'],
        ];

        foreach ($students as [$nisn, $name, $birth, $cohort, $className]) {

            // Password awal = tanggal lahir (ddmmyy)
            $password = \Carbon\Carbon::parse($birth)->format('dmy');

            $user = User::firstOrCreate(
                ['username' => $nisn],
                [
                    'name' => $name,
                    'password' => Hash::make($password),
                    'role' => 'student',
                    'must_change_password' => true,
                ]
            );

            // Cari kelas berdasarkan tahun ajaran aktif
            $classRoom = ClassRoom::where('name', $className)
                ->where('academic_year_id', $academicYear->id)
                ->firstOrFail();

            // Buat data siswa
            $student = Student::firstOrCreate(
                ['nisn' => $nisn],
                [
                    'user_id' => $user->id,
                    'birth_date' => $birth,
                    'cohort_year' => $cohort,
                    'class_room_id' => $classRoom->id,
                ]
            );

            // Simpan riwayat kelas siswa
            StudentClassHistory::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                ],
                [
                    'class_room_id' => $classRoom->id,
                ]
            );

            // =========================
            // ORANG TUA
            // =========================
            $parentUser = User::firstOrCreate(
                ['username' => 'ortu-' . $nisn],
                [
                    'name' => 'Orang Tua ' . $name,
                    'password' => Hash::make('OrtuDev!2026'),
                    'role' => 'parent',
                ]
            );

            $parent = ParentProfile::firstOrCreate(
                ['user_id' => $parentUser->id],
                [
                    'phone' => '0800000000',
                ]
            );

            $parent->students()->syncWithoutDetaching($student);
        }

        // =========================
        // COURSE
        // =========================
        $math = Course::firstOrCreate(
            ['name' => 'Matematika'],
            [
                'teacher_id' => $teachers[0]->id,
                'target_grade' => 7,
                'description' => 'Course demo Matematika',
            ]
        );

        Course::firstOrCreate(
            ['name' => 'Bahasa Indonesia'],
            [
                'teacher_id' => $teachers[1]->id,
                'target_grade' => 8,
                'description' => 'Course demo Bahasa Indonesia',
            ]
        );

        Course::firstOrCreate(
            ['name' => 'IPA'],
            [
                'teacher_id' => $teachers[0]->id,
                'target_grade' => 9,
                'description' => 'Course demo IPA',
            ]
        );

        // =========================
        // SECTION & MATERIAL
        // =========================
        $section = $math->sections()->firstOrCreate(
            ['title' => 'Pertemuan 1'],
            ['position' => 1]
        );

        $section->materials()->firstOrCreate(
            ['title' => 'Persamaan Linear'],
            [
                'type' => 'text',
                'body' => 'Materi pengantar persamaan linear satu variabel.',
                'target_grade' => 7,
            ]
        );

        $section->materials()->firstOrCreate(
            ['title' => 'Video Contoh Soal'],
            [
                'type' => 'video',
                'file_path' => 'https://example.com/video-demo',
                'target_grade' => 7,
            ]
        );

        // =========================
        // ASSESSMENT
        // =========================
        $quiz = Assessment::firstOrCreate(
            ['title' => 'Kuis Persamaan Linear'],
            [
                'section_id' => $section->id,
                'target_grade' => 7,
                'description' => 'Kuis singkat otomatis dan manual.',
                'passing_score' => 70,
            ]
        );

        Assessment::firstOrCreate(
            ['title' => 'UTS Matematika'],
            [
                'section_id' => $section->id,
                'target_grade' => 7,
                'description' => 'Ujian demo dengan timer server-side.',
                'is_exam' => true,
                'duration_minutes' => 30,
                'passing_score' => 75,
            ]
        );

        // =========================
        // QUESTIONS
        // =========================
        $mc = Question::firstOrCreate(
            [
                'assessment_id' => $quiz->id,
                'prompt' => 'Hasil dari 2x = 10 adalah ...',
            ],
            [
                'type' => 'multiple_choice',
                'points' => 50,
                'position' => 1,
            ]
        );

        $mc->options()->firstOrCreate(
            ['option_text' => 'x = 5'],
            ['is_correct' => true]
        );

        $mc->options()->firstOrCreate(
            ['option_text' => 'x = 8'],
            ['is_correct' => false]
        );

        Question::firstOrCreate(
            [
                'assessment_id' => $quiz->id,
                'prompt' => 'Persamaan linear memiliki variabel berpangkat satu.',
            ],
            [
                'type' => 'true_false',
                'correct_answer' => 'true',
                'points' => 25,
                'position' => 2,
            ]
        );

        Question::firstOrCreate(
            [
                'assessment_id' => $quiz->id,
                'prompt' => 'Jelaskan langkah menyelesaikan 3x + 2 = 11.',
            ],
            [
                'type' => 'essay',
                'points' => 25,
                'position' => 3,
            ]
        );

        // =========================
        // PROFILING
        // =========================
        ProfilingQuestion::firstOrCreate(
            ['code' => 'time_management'],
            [
                'prompt' => 'Saya mampu mengatur waktu belajar dengan baik.',
                'material_keyword' => 'Manajemen Waktu',
            ]
        );
    }
}