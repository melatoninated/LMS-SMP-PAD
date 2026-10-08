# SMP LMS Laravel

Learning Management System untuk sekolah SMP berbasis Laravel, SQLite/MySQL, dan Tailwind CSS.


## Requirement Runtime

- PHP 8.2+
- Composer
- Node.js dan npm

Di environment saat ini `php` dan `composer` belum tersedia, sehingga saya belum bisa menjalankan `composer install`, migration, atau test Laravel secara lokal.

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000`.

## Akun Demo

| Role | Username | Password |
| --- | --- | --- |
| Admin | `admin@example.com` | `AdminDev!2026` |
| Guru | `guru1@example.com` | `GuruDev!2026` |
| Guru | `guru2@example.com` | `GuruDev!2026` |
| Siswa | `0012345671` | `2012-05-17` |
| Siswa | `0012345673` | `2011-03-12` |
| Orang Tua | `ortu-0012345671` | `OrtuDev!2026` |

Password awal siswa adalah tanggal lahir dan disimpan menggunakan hashing Laravel. Siswa wajib mengganti password sebelum masuk dashboard.

## Struktur Penting

- `app/Models`: model Eloquent untuk user, siswa, guru, orang tua, course, assessment, exam, dan profiling.
- `app/Http/Controllers`: controller per role dan flow LMS.
- `app/Http/Middleware`: role middleware dan force change password.
- `app/Policies`: proteksi backend untuk course guru dan submission.
- `app/Services`: business logic kenaikan kelas, assessment, dan exam timer server-side.
- `database/migrations`: schema relasional dengan FK dan index.
- `database/seeders/DatabaseSeeder.php`: seed akun demo dan materi contoh.
- `app/Imports` dan `app/Exports`: import siswa dan export nilai via Laravel Excel.
- `resources/views`: Blade UI dengan Tailwind CSS.
- `tests/Feature/LmsBusinessTest.php`: test business rule utama.

## Test

```bash
php artisan test
```

## API

API tersedia di prefix `/api` dan memakai Bearer token Laravel Sanctum. Jalankan migration setelah menarik perubahan ini agar tabel token dibuat.

```bash
php artisan migrate
```

Login melalui `POST /api/auth/login` dengan JSON `username`, `password`, dan opsional `device_name`. Respons berisi `token`; kirim token tersebut pada request berikutnya sebagai `Authorization: Bearer <token>`.

Endpoint utama: `GET /api/me`, `PUT /api/auth/password`, `POST /api/auth/logout`, serta grup role `/api/admin`, `/api/teacher`, `/api/student`, dan `/api/parent`. Lihat `routes/api.php` untuk daftar endpoint lengkap dan format parameter validasi di controller `app/Http/Controllers/Api`.

Test mencakup password default, reset password admin, authorization guru, kenaikan kelas otomatis, aturan akses assessment kelas sebelumnya, grading otomatis/manual, timer ujian server-side, dan parent-child access.

## Catatan Keamanan Ujian

Proteksi copy, paste, text selection, fullscreen, dan log keluar fullscreen dibuat sebagai proteksi dasar browser. JavaScript tidak bisa menjamin keamanan absolut terhadap developer tools. Kontrol utama tetap berada di backend melalui authorization, server-side `exam_sessions.expires_at`, submission state, dan activity log.
