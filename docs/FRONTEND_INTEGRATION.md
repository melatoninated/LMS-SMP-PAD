# Integrasi Frontend (Vercel) dengan Backend (Wasmer)

Dokumen ini menggantikan alur clone dan menjalankan backend di komputer anggota frontend. Backend Laravel dideploy satu kali di Wasmer; frontend Next.js dideploy sendiri di Vercel lalu memanggil URL API Wasmer melalui HTTPS.

```text
Browser → Frontend Next.js (Vercel) → API Laravel (Wasmer) → MySQL Wasmer
                                             └──────────→ volume persisten (unggahan)
```

Gunakan contoh URL berikut hanya sebagai placeholder:

```text
Backend:  https://lms-api-<owner>.wasmer.app
Frontend: https://lms-web.vercel.app
```

Jangan menyimpan `APP_KEY`, password, token, atau kredensial database di Git maupun pada environment variable `NEXT_PUBLIC_*`.

## Tanggung jawab

| Pihak | Tanggung jawab |
| --- | --- |
| Backend | Deploy Wasmer, database, migrasi, secret Laravel, storage, dan CORS. |
| Frontend | Deploy Next.js ke Vercel dan mengisi URL API yang diberikan backend. |
| Bersama | Menguji login, request Bearer token, CORS, serta unggahan dari domain produksi. |

## Deploy backend Laravel di Wasmer

### 1. Siapkan penyimpanan produksi

Konfigurasi lokal proyek ini menggunakan SQLite dan filesystem lokal. Jangan gunakan keduanya sebagai penyimpanan produksi pada runtime edge yang stateless. Gunakan database MySQL terkelola Wasmer dan volume persisten untuk unggahan.

Buat `wasmer.toml` di akar repository backend:

```toml
[dependencies]
"php/php" = "=8.3.4"

[fs]
"/app/" = "."
"/config/" = "config"

[[command]]
name = "run"
module = "php/php:php"
runner = "wasi"

[command.annotations.wasi]
main-args = ["-t", "/app/public", "-S", "localhost:8080"]
env = ["PHPRC=/config/"]
```

Buat `config/php.ini`. Limit ini sesuai dengan validasi unggahan aplikasi yang dapat menerima hingga 50 MB.

```ini
display_errors = Off
log_errors = On
upload_max_filesize = 50M
post_max_size = 55M
max_execution_time = 120
opcache.validate_timestamps = 0
opcache.file_update_protection = 0
opcache.max_file_size = 0
```

Setelah deploy pertama menghasilkan nama package, commit `app.yaml` berikut dengan nilai placeholder diganti. Wasmer akan mengisi `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USERNAME`, dan `DB_PASSWORD` untuk capability database.

```yaml
kind: wasmer.io/App.v0
name: lms-api
package: <namespace-wasmer>/<nama-package>
env:
  APP_ENV: production
  APP_DEBUG: "false"
  APP_URL: https://lms-api-<owner>.wasmer.app
  DB_CONNECTION: mysql
  CACHE_STORE: database
  SESSION_DRIVER: database
  QUEUE_CONNECTION: sync
  FILESYSTEM_DISK: public
  CORS_ALLOWED_ORIGINS: https://lms-web.vercel.app
capabilities:
  database:
    engine: mysql
  instaboot:
    requests:
      - path: /up
locality:
  regions:
    - fr-roub1
volumes:
  - name: lms-uploads
    mount: /app/storage/app/public
scaling:
  mode: single_concurrency
```

Pilih region database yang tersedia dan dekat dengan pengguna; `fr-roub1` di atas hanya contoh. Aplikasi Laravel ini menyediakan health check publik pada `/up`. Volume pada `/app/storage/app/public` membuat disk Laravel `public` tetap persisten. Sebelum deployment, buat `public/storage` melalui `php artisan storage:link` dan pastikan symbolic link itu ada dalam artefak/package deployment; membuatnya hanya dalam sesi SSH tidak akan bertahan pada release berikutnya.

### 2. Instal, login, dan deploy

Di PowerShell pemilik backend:

```powershell
iwr https://win.wasmer.io -useb | iex
wasmer login
wasmer --version
wasmer run .
wasmer deploy
```

`wasmer deploy` memandu pemilihan owner, nama aplikasi, dan publikasi package. Simpan URL `.wasmer.app` yang tampil. Setelah `app.yaml` dilengkapi dengan package dan domain yang sesungguhnya, deploy ulang:

```powershell
wasmer deploy
```

Pastikan `composer.lock` ikut di-commit agar dependency produksi konsisten.

### 3. Simpan secret dan jalankan migrasi

Hasilkan `APP_KEY` satu kali lalu simpan output sebagai secret Wasmer. Jangan menggantinya saat redeploy.

```powershell
php artisan key:generate --show
wasmer app secrets create APP_KEY "base64:<output-key-di-atas>"
```

Jalankan migrasi terhadap database produksi **sekali**, dari environment yang menerima variable database Wasmer:

```powershell
wasmer ssh --app lms-api
```

Di sesi aplikasi, dari `/app`, jalankan:

```bash
php artisan migrate --force
php artisan db:seed --force
```

SSH aplikasi membutuhkan paket/izin Wasmer yang mendukungnya. Bila tidak tersedia, gunakan fasilitas job pre-deployment atau maintenance resmi pada paket Wasmer Anda; jangan menjalankan migrasi ke database lokal sebagai pengganti. Jangan gunakan `migrate:fresh` di produksi. Jalankan seeder hanya bila aman untuk dijalankan ulang dan memang diperlukan untuk data awal.

### 4. Validasi backend

```powershell
curl.exe -i https://lms-api-<owner>.wasmer.app/up
```

Periksa log Wasmer bila respons `500`. Sebelum frontend dipakai, backend harus memiliki `APP_DEBUG=false`, secret `APP_KEY`, database yang telah dimigrasi, dan URL production Vercel pada CORS.

## Konfigurasi frontend

Setel URL backend pada environment frontend. Jangan akhiri nilai dengan garis miring.

```env
NEXT_PUBLIC_API_URL=https://lms-api-<owner>.wasmer.app/api
```

Nilai harus mencakup `/api`, memakai HTTPS, dan **tidak** diakhiri garis miring. `NEXT_PUBLIC_` membuat URL ini tersedia di browser; karena itu jangan memakai prefix ini untuk rahasia apa pun.

Untuk pengembangan frontend lokal (opsional), anggota frontend cukup membuat `.env.local` di repository Next.js sendiri:

```env
NEXT_PUBLIC_API_URL=https://lms-api-<owner>.wasmer.app/api
```

## Autentikasi

Gunakan `POST /auth/student/login` untuk siswa, `POST /auth/parent/login` untuk orang tua, dan `POST /auth/staff/login` untuk guru/admin. Siswa dan orang tua memakai NISN anak yang sama; pemisahan endpoint mencegah ambiguitas role.

```json
{
  "username": "G001",
  "password": "GuruDev!2026",
  "role": "teacher",
  "remember": true,
  "device_name": "frontend-web"
}
```

Simpan `token` dari respons, kemudian kirimkan pada setiap endpoint terlindungi:

```http
Authorization: Bearer <token>
Accept: application/json
```

Jika `must_change_password` bernilai `true`, arahkan pengguna ke form ubah password dan panggil `PUT /auth/password` dengan `current_password`, `password`, serta `password_confirmation` sebelum membuka endpoint dashboard per role.

## Assessment dan keamanan ujian

Respons assessment memuat `require_fullscreen`, `anti_copy_paste`, `duration_minutes`, dan `remaining_seconds`. Next.js perlu menerapkan Fullscreen API serta memblokir copy/paste, selection, dan shortcut clipboard bila flag terkait bernilai `true`; backend tetap menjadi sumber waktu dan penentu submit.

Simpan jawaban teks secara berkala dengan `PUT /student/assessments/{assessment}/draft` berisi `{ "answers": { "question_id": "jawaban" } }`. Draft digunakan saat scheduler backend melakukan auto-submit ujian yang kedaluwarsa. Untuk jawaban unggahan, kirim `multipart/form-data` ke endpoint submit dengan field `files[<questionId>]`.

Gunakan `is_read_only` pada detail course dan `can_attempt` pada detail assessment untuk menonaktifkan aksi pengerjaan pada materi/tugas historis.

## CORS untuk domain Vercel

Tambahkan origin frontend production ke `CORS_ALLOWED_ORIGINS` pada konfigurasi Wasmer backend, dipisahkan dengan koma bila ada custom domain. Origin hanya berisi skema dan domain: tanpa `/api` dan tanpa trailing slash.

```env
CORS_ALLOWED_ORIGINS=https://lms-web.vercel.app,https://app.sekolah.example
```

Setelah mengubah `app.yaml`, deploy ulang:

```powershell
wasmer deploy
```

Jangan gunakan `*` di produksi. Vercel Preview memiliki domain berbeda setiap deployment; gunakan domain production yang stabil untuk integrasi utama. Jika preview perlu diuji terhadap API produksi, tambahkan origin preview tertentu sementara lalu hapus setelah selesai.

## Deploy frontend Next.js ke Vercel

Anggota frontend mengerjakan langkah ini dari repository Next.js, tanpa repository backend.

1. Push repository frontend ke GitHub, GitLab, atau Bitbucket.
2. Di Vercel pilih **Add New → Project**, lalu import repository tersebut.
3. Pastikan Framework Preset terdeteksi sebagai **Next.js**. Pada monorepo, pilih Root Directory yang berisi `package.json` Next.js.
4. Buka **Settings → Environment Variables**, lalu tambahkan variable berikut untuk **Production**:

   | Nama | Nilai |
   | --- | --- |
   | `NEXT_PUBLIC_API_URL` | `https://lms-api-<owner>.wasmer.app/api` |

5. Deploy dan catat domain production Vercel, misalnya `https://lms-web.vercel.app`.
6. Berikan domain tersebut kepada pemilik backend. Backend memasukkannya ke `CORS_ALLOWED_ORIGINS` dan menjalankan `wasmer deploy`.
7. Uji dari domain Vercel setelah backend selesai redeploy.

Perubahan environment variable Vercel hanya diterapkan pada deployment baru; lakukan **Redeploy** setiap kali `NEXT_PUBLIC_API_URL` diubah.

Alternatif CLI dari akar repository frontend:

```powershell
npm install -g vercel
vercel login
vercel
vercel env add NEXT_PUBLIC_API_URL production
vercel --prod
```

Saat `vercel env add`, masukkan URL Wasmer lengkap dengan `/api`. Jangan membuat proxy API yang meneruskan kredensial/token kecuali memang diperlukan; request browser langsung ke Wasmer sudah didukung oleh konfigurasi CORS API ini.

## Axios (Next.js)

```js
import axios from 'axios';

export const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL,
  headers: { Accept: 'application/json' },
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});
```

Gunakan [kontrak API](API_CONTRACT.md) sebagai daftar request dan payload yang siap diuji.

## Checklist integrasi sebelum rilis

- [ ] `NEXT_PUBLIC_API_URL` memakai `https://<backend-wasmer>/api`, bukan `localhost`, tunnel, atau URL dengan trailing slash.
- [ ] `APP_KEY` berada di Wasmer Secrets; `APP_DEBUG=false`; tidak ada secret dalam Git atau variable `NEXT_PUBLIC_*`.
- [ ] `DB_CONNECTION=mysql` dan migrasi produksi sudah sukses tanpa `migrate:fresh`.
- [ ] Domain production Vercel persis sama dengan salah satu nilai `CORS_ALLOWED_ORIGINS`.
- [ ] Login dari domain Vercel tidak menghasilkan error CORS di Browser DevTools.
- [ ] Request terlindungi mengirim `Authorization: Bearer <token>`; tanpa token menghasilkan `401`.
- [ ] Satu akun dari setiap role dapat login dan membuka data yang diizinkan.
- [ ] Unggahan diuji setelah backend redeploy untuk memastikan file tersimpan pada volume persisten.

## Troubleshooting

| Gejala | Penyebab umum | Perbaikan |
| --- | --- | --- |
| Error CORS di browser | Origin Vercel tidak sama persis dengan konfigurasi backend. | Tambahkan `https://<domain-vercel>` ke `CORS_ALLOWED_ORIGINS`, lalu `wasmer deploy`. |
| Frontend masih meminta `localhost` | Env Vercel belum diisi atau deployment lama masih aktif. | Set `NEXT_PUBLIC_API_URL` di Production dan redeploy Vercel. |
| Backend `500` | Secret, DB, dependency, atau migrasi belum siap. | Periksa log Wasmer; validasi `APP_KEY`, `DB_CONNECTION=mysql`, dan status migrasi tanpa membocorkan secret. |
| Data/file hilang setelah deploy | SQLite atau filesystem runtime dipakai. | Gunakan managed database serta volume Wasmer pada path storage. |
| File upload ditolak | Limit HTTP PHP lebih kecil dari validasi Laravel. | Pastikan `config/php.ini` ter-mount lewat `PHPRC`, kemudian redeploy. |
| Preview Vercel gagal CORS | Domain Preview berbeda dari Production. | Uji lewat production atau tambahkan origin preview spesifik sementara. |

## Referensi resmi

- [Deploy Laravel di Wasmer Edge](https://docs.wasmer.io/edge/guides/laravel/)
- [Konfigurasi aplikasi, database, dan volume Wasmer](https://docs.wasmer.io/edge/configuration/)
- [Volume persisten Wasmer](https://docs.wasmer.io/edge/guides/volumes/)
- [Environment variables Vercel](https://vercel.com/docs/environment-variables)
- [Deployment environment Vercel](https://vercel.com/docs/deployments/environments)
