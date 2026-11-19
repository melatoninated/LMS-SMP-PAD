# Kontrak API SMP LMS

Kontrak ini bersumber dari `routes/api.php` dan controller API. Base URL: `{BASE_URL}/api`. Semua payload/respons JSON kecuali endpoint file; selalu kirim `Accept: application/json`.

## Konvensi

- Selain login, endpoint memerlukan `Authorization: Bearer {token}`.
- Peran: `admin`, `teacher`, `student`, `parent`. Endpoint peran mensyaratkan `must_change_password=false`; sebelum itu pengguna hanya dapat memakai `GET /me`, `PUT /auth/password`, dan logout.
- Token tanpa `remember: true` berlaku satu hari. Login dibatasi lima request per menit.
- ID pada path adalah ID numerik resource. Gunakan ISO 8601 untuk waktu dan boolean JSON untuk flag.
- Sukses list/detail: `200` `{ "data": ... }`; create: `201`; aksi tanpa body: `204`. Error umum: `401` token tidak valid, `403` tidak berwenang/aturan bisnis, `404` tidak ditemukan, `409` percobaan sudah terkumpul, `422` validasi gagal.
- Error validasi berbentuk `{ "message": "...", "errors": { "field": ["..."] } }`. List belum dipaginasi.

## Autentikasi

| Method & path | Akses | Request | Respons sukses |
| --- | --- | --- | --- |
| `POST /auth/login` | Publik | `username`, `password`, `role` (`admin\|student\|teacher\|parent`); opsional `remember`, `device_name` | `200` `{token, token_type:"Bearer", must_change_password, user}` |
| `POST /auth/student/login` | Publik | `username`, `password`; opsional `remember`, `device_name` | Sama; role `student` |
| `POST /auth/parent/login` | Publik | Sama | Sama; role `parent` |
| `POST /auth/staff/login` | Publik | `username`, `password`, `role` (`admin\|teacher`); opsional `remember`, `device_name` | Sama |
| `GET /me` | Token | — | `200` `{user}` |
| `PUT /auth/password` | Token | `current_password`, `password` (min. 8), `password_confirmation` | `200` `{message}`; menonaktifkan kewajiban ganti password |
| `POST /auth/logout` | Token | — | `204`; mencabut token saat ini |

`user` memuat `id`, `name`, `username`, `email`, `role`, `must_change_password`, `is_active`, serta profil relevan (`student`, `teacher`, atau `parent_profile`). Kredensial salah menghasilkan `422`.

## Admin

Seluruh endpoint memerlukan role `admin`.

| Method & path | Request utama | Respons |
| --- | --- | --- |
| `GET /admin/dashboard` | — | Jumlah data, tahun aktif, kelas, alert |
| `GET, POST /admin/teachers` | POST: `name`, `email`, `password`, `password_confirmation`, `employee_no`; opsional `phone` | List / `201` |
| `PUT, DELETE /admin/teachers/{teacher}` | PUT: `name`, `email`, `employee_no`; opsional `phone` | Data terbaru / `204` soft delete |
| `GET, POST /admin/students` | POST: `name`, `nisn` (10 digit), `birth_date`, `cohort_year`; opsional `class_room_id` | List / `201` |
| `PUT, DELETE /admin/students/{student}` | PUT: `name`, `nisn` (10 digit), `birth_date` (`Y-m-d`), `cohort_year`; opsional `class_room_id` | Data terbaru / `204` soft delete |
| `POST /admin/students/{student}/reset-password` | — | `200`; password direset ke tanggal lahir `YYYYMMDD` |
| `POST /admin/students/import` | Multipart: `file` XLSX, maks. 10 MB | `201` `{message, imported}` atau `422` `{failures}` |
| `GET, POST /admin/parents` | POST: `name`, `student_id`; opsional `phone`, `address` | List / `201` |
| `PUT, DELETE /admin/parents/{parent}` | PUT: `name`, `student_id`; opsional `phone`, `address` | Data terbaru / `204` soft delete |
| `GET, POST /admin/classes` | POST: `name`, `grade_level` (7/8/9), `academic_year_id` | List / `201` |
| `PUT, DELETE /admin/classes/{classRoom}` | PUT: `name`, `grade_level` (7/8/9), `academic_year_id`; opsional `is_active` | Data terbaru / `204` soft delete |
| `GET, POST /admin/academic-years` | POST: `name`, `start_year` | List / `201` |
| `PUT, DELETE /admin/academic-years/{academicYear}` | PUT: `name` (`YYYY/YYYY`), `start_year` | Data terbaru / `204` soft delete |
| `POST /admin/academic-years/{academicYear}/activate` | — | `200` tahun ajaran aktif terbaru |
| `GET, POST /admin/subjects` | POST: `code`, `name` | List / `201` |
| `PUT, DELETE /admin/subjects/{subject}` | PUT: `code`, `name` | Data terbaru / `204` soft delete |
| `GET, POST /admin/teaching-assignments` | POST: `teacher_id`, `subject_id`, `academic_year_id`, `class_room_id`, `grade_level` (7/8/9) | List / `201` |
| `PUT, DELETE /admin/teaching-assignments/{teachingAssignment}` | PUT: field yang sama seperti POST | Data terbaru / `204` soft delete |

Setiap ID relasi harus ada. Kelas pada teaching assignment wajib memiliki tingkat dan tahun ajaran yang sama dengan `grade_level` dan `academic_year_id`; pelanggaran menghasilkan `422`.

## Guru

Seluruh endpoint memerlukan role `teacher`; guru hanya dapat mengelola course dan turunan miliknya.

| Method & path | Request utama | Respons / aturan |
| --- | --- | --- |
| `GET /teacher/dashboard` | — | Course guru dan jumlah submission perlu dinilai |
| `GET /teacher/courses` | — | Daftar course guru |
| `POST /teacher/courses` | `teaching_assignment_id`; opsional `name`, `description` | `201`; penugasan harus milik guru aktif |
| `GET /teacher/courses/{course}` | — | Course, section, materi, assessment, soal, opsi |
| `PUT /teacher/courses/{course}` | `name`; opsional `description`, `is_active` | Course terbaru |
| `DELETE /teacher/courses/{course}` | — | `204`; soft delete/nonaktif |
| `POST /teacher/courses/{course}/sections` | `title` | `201`; posisi ditentukan server |
| `POST /teacher/sections/{section}/materials` | `title`, `type` (`text\|pdf\|video\|audio\|file`), `target_grade` (7/8/9); opsional `body`, `file_path`, `file` | `201`; multipart jika file. Selain `text`, file atau URL wajib ada. |
| `POST /teacher/sections/{section}/assessments` | `title`, `assessment_type` (`practice\|midterm\|final\|daily_exam`), `target_grade`, `is_published`, `duration_minutes` (min. 1), `end_at`; opsional `description`, `is_exam`, `require_fullscreen`, `anti_copy_paste`, `start_at`, `maximum_attempt`, `passing_score` | `201`; `end_at` harus sesudah sekarang dan `start_at` bila ada |
| `POST /teacher/assessments/{assessment}/questions` | `type`, `prompt`, `points` (0–100); opsional `correct_answer`, `options` | `201` soal dan opsi |
| `GET /teacher/submissions` | — | Submission, siswa, assessment, jawaban |
| `POST /teacher/submissions/{submission}/grade` | `score` (0–100, tidak melebihi total poin); opsional `feedback` | Submission bernilai |

`multiple_choice` membutuhkan minimal dua `options` (`{text, is_correct}`) dengan tepat satu jawaban benar. `true_false` membutuhkan `correct_answer` bernilai `true`/`false`. Soal `essay` dan `file_upload` perlu nilai manual.

## Siswa

Seluruh endpoint memerlukan role `student`.

| Method & path | Request utama | Respons / aturan |
| --- | --- | --- |
| `GET /student/dashboard` | — | Siswa, kelas, tingkat yang diharapkan, kebutuhan update kelas, course terlihat |
| `GET /student/courses` | — | Course terlihat dan `expected_grade` |
| `POST /student/courses/{course}/enroll` | — | `200`; hanya course aktif yang dapat diakses |
| `GET /student/courses/{course}` | — | Course, materi, assessment published, `is_read_only` untuk tahun lama |
| `POST /student/materials/{material}/complete` | — | `200`; menyimpan progres |
| `GET /student/assessments/{assessment}` | — | Assessment tanpa kunci opsi, `can_attempt`, dan (untuk ujian) `exam_session`, `remaining_seconds` |
| `PUT /student/assessments/{assessment}/draft` | `answers` object (`{questionId: value}`) | `204`; hanya ketika masih boleh mencoba |
| `POST /student/assessments/{assessment}/submit` | Opsional `answers` object, `files` array | `201`; gunakan multipart untuk file, key `files[{questionId}]` |
| `POST /student/exam-sessions/{examSession}/logs` | `event`; opsional `detail` | `204`; hanya sesi aktif milik siswa yang belum habis |
| `GET /student/profile` | — | Kelas yang dapat dipilih, pertanyaan profiling, rekomendasi |
| `PUT /student/profile/class` | `class_room_id` | Siswa terbaru; kelas harus aktif dan sesuai tingkat/tahun aktif |
| `PUT /student/profile/profiling` | `answers` object; setiap nilai integer 1–5 | `200` |

Assessment hanya dapat dikerjakan pada jadwal aktif dan dalam batas `maximum_attempt`. Membuka assessment ujian memulai/melanjutkan sesi di server; frontend harus memakai `remaining_seconds` untuk tampilan dan tidak menjadikan timer klien sebagai sumber kebenaran. Submit setelah waktu habis ditolak; draft dipakai ketika service auto-submit memproses sesi kadaluarsa.

## Orang tua

| Method & path | Akses | Respons |
| --- | --- | --- |
| `GET /parent/dashboard` | `parent` | Anak yang terhubung, progres per course, submission, dan alert nilai tertunda |

Implementasi saat ini menghubungkan satu profil orang tua ke satu siswa.

## Contoh submit

```json
{
  "answers": {
    "12": "34",
    "13": "true",
    "14": "Penjelasan jawaban."
  }
}
```

Untuk file, gunakan `multipart/form-data` dan tambahkan `files[14]`. Jangan pernah mengirim atau mengandalkan `is_correct` dari client siswa.
