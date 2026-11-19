# SMP LMS

Backend Learning Management System (LMS) untuk SMP, dengan UI Blade dan REST API untuk frontend terpisah. Dibangun dengan Laravel 12, Sanctum, SQLite/MySQL, Vite, dan Tailwind CSS.

## Fitur

- Peran admin, guru, siswa, dan orang tua.
- Pengelolaan tahun ajaran, kelas, pengguna, mata pelajaran, serta penugasan mengajar.
- Course, materi, assessment, soal, pengumpulan, penilaian, dan import siswa XLSX.
- Ujian bertimer server-side, draft jawaban, log aktivitas, profiling, dan riwayat kelas.

## Prasyarat

- PHP 8.2+ (Composer dikunci ke platform PHP 8.3)
- Composer 2
- Node.js 20+ dan npm
- SQLite untuk lokal atau MySQL untuk produksi

## Menjalankan secara lokal

Perintah berikut ditujukan untuk PowerShell.

```powershell
composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File -Path database/database.sqlite -Force
php artisan migrate --seed
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000`. Jalankan `npm run dev` pada terminal lain selama mengembangkan aset. Konfigurasi bawaan `.env.example` telah memakai SQLite di `database/database.sqlite`. Untuk MySQL, ubah `DB_CONNECTION=mysql` dan isi seluruh variabel `DB_*` sebelum migrasi.

## Akun data seed

| Peran | Username | Password awal |
| --- | --- | --- |
| Admin | `admin@example.com` | `AdminDev!2026` |
| Guru | `G001` atau `G002` | `GuruDev!2026` |
| Siswa | `0012345671` | `20120517` |
| Siswa | `0012345673` | `20110312` |
| Orang tua | `0012345671` | `20120517` |

Password awal siswa dan orang tua memakai tanggal lahir anak (`YYYYMMDD`). Semua akun seed siswa dan orang tua wajib mengganti password melalui API sebelum memakai endpoint fitur peran.

## API

Base URL lokal adalah `http://127.0.0.1:8000/api`. API memakai Bearer token Laravel Sanctum, bukan cookie lintas domain.

```http
POST /api/auth/login
Accept: application/json
Content-Type: application/json

{
  "username": "G001",
  "password": "GuruDev!2026",
  "role": "teacher",
  "device_name": "frontend-web",
  "remember": true
}
```

Gunakan `token` dari respons pada request yang terlindungi:

```http
Authorization: Bearer <token>
Accept: application/json
```

Kontrak lengkap endpoint, peran, payload, validasi, respons, dan aturan integrasi tersedia di [docs/API_CONTRACT.md](docs/API_CONTRACT.md). Kontrak ini adalah acuan frontend; verifikasi rute dengan `php artisan route:list --path=api`.

Validasi Laravel mengembalikan `422` dengan `message` dan `errors`; autentikasi/otorisasi menghasilkan `401`/`403`. Respons list dibungkus dalam `{ "data": [...] }` dan belum dipaginasi.

## Unggahan

Kirim materi dan jawaban file menggunakan `multipart/form-data`. Jawaban assessment memakai key `files[{questionId}]`. Batas aplikasi: gambar 5 MB, PDF 10 MB, audio 15 MB, video 50 MB; format lain ditolak. Buat storage link untuk menyajikan file secara lokal:

```powershell
php artisan storage:link
```

## Pemeriksaan

```powershell
php artisan route:list --path=api
npm run build
```

Proyek saat ini belum memiliki suite test yang dapat dijalankan melalui `php artisan test`. Health check bawaan Laravel tersedia pada `GET /up`, bukan di bawah `/api`.

## Deployment dan integrasi frontend

Panduan Next.js/Vercel dan Wasmer ada di [docs/FRONTEND_INTEGRATION.md](docs/FRONTEND_INTEGRATION.md). Pada produksi gunakan MySQL serta volume persisten untuk `storage/app/public`, simpan `APP_KEY` sebagai secret, set `APP_DEBUG=false`, dan isi `CORS_ALLOWED_ORIGINS` dengan origin frontend HTTPS tanpa path/trailing slash.

## Struktur utama

- `routes/api.php` — REST API dan middleware peran.
- `app/Http/Controllers/Api` — implementasi API.
- `app/Services` — aturan assessment, ujian, unggahan, dan tahun ajaran.
- `database/migrations`, `database/seeders` — skema dan data demo.
- `resources/views` — UI Blade.
- `docs/API_CONTRACT.md` — kontrak integrasi API.
