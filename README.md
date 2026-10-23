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
| Guru | `G001` | `GuruDev!2026` |
| Guru | `G002` | `GuruDev!2026` |
| Siswa | `0012345671` | `17052012` |
| Siswa | `0012345673` | `12032011` |
| Orang Tua | `ortu-0012345671` | `OrtuDev!2026` |

Password awal siswa adalah tanggal lahir dalam format `DDMMYYYY` dan disimpan menggunakan hashing Laravel. Siswa wajib mengganti password sebelum masuk dashboard. Guru login dengan NIP; admin dan orang tua menggunakan username akun masing-masing.

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

Login melalui `POST /api/auth/login` dengan JSON `username`, `password`, `role`, serta opsional `remember` dan `device_name`. Nilai `role` harus salah satu dari `admin`, `student`, `teacher`, atau `parent`; ini harus dikirim sesuai pilihan **Login sebagai** di frontend. Respons berisi `token`; kirim token tersebut pada request berikutnya sebagai `Authorization: Bearer <token>`. Token tanpa `remember: true` berlaku satu hari.

Endpoint utama: `GET /api/me`, `PUT /api/auth/password`, `POST /api/auth/logout`, serta grup role `/api/admin`, `/api/teacher`, `/api/student`, dan `/api/parent`. Lihat `routes/api.php` untuk daftar endpoint lengkap dan format parameter validasi di controller `app/Http/Controllers/Api`.

## Dokumentasi Postman

Koleksi Postman siap pakai tersedia di [`postman/collections/SMP LMS API.postman_collection.json`](postman/collections/SMP%20LMS%20API.postman_collection.json), dengan environment lokal di [`postman/environments/LMS Local.postman_environment.json`](postman/environments/LMS%20Local.postman_environment.json). Keduanya juga terdaftar otomatis oleh Postman Local View melalui folder `postman/`.

Pilih environment **LMS Local**, sesuaikan `baseUrl` bila perlu, lalu jalankan request **Authentication → Login**. Token respons akan otomatis disimpan ke variabel `token` dan digunakan oleh seluruh request yang membutuhkan autentikasi. Variabel ID (misalnya `courseId` dan `assessmentId`) dapat diganti dari environment sesuai data database.

Test mencakup password default, reset password admin, authorization guru, kenaikan kelas otomatis, aturan akses assessment kelas sebelumnya, grading otomatis/manual, timer ujian server-side, dan parent-child access.

## Catatan Keamanan Ujian

Proteksi copy, paste, text selection, fullscreen, dan log keluar fullscreen dibuat sebagai proteksi dasar browser. JavaScript tidak bisa menjamin keamanan absolut terhadap developer tools. Kontrol utama tetap berada di backend melalui authorization, server-side `exam_sessions.expires_at`, submission state, dan activity log.
