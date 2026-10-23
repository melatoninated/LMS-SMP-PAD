# Integrasi Frontend

## Konfigurasi frontend

Setel URL backend pada environment frontend. Jangan akhiri nilai dengan garis miring.

```env
NEXT_PUBLIC_API_URL=https://<url-tunnel>/api
```

Saat backend dan frontend dijalankan pada komputer yang sama, gunakan:

```env
NEXT_PUBLIC_API_URL=http://127.0.0.1:8000/api
```

## Autentikasi

Masuk melalui `POST /auth/login`. Field `role` wajib dan hanya menerima `admin`, `teacher`, `student`, atau `parent`.

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

## CORS dan tunnel

Tambahkan asal frontend ke `CORS_ALLOWED_ORIGINS` di file `.env` backend, dipisahkan dengan koma.

```env
CORS_ALLOWED_ORIGINS=http://localhost:5173,https://frontend-tim.example
```

Setelah mengubahnya jalankan:

```powershell
php artisan config:clear
```

Untuk kerja lintas jaringan, jalankan backend pada port 8000 lalu publikasikan port tersebut dengan tunnel:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
cloudflared tunnel --url http://localhost:8000
```

Berikan URL HTTPS yang muncul ke tim frontend sebagai nilai `VITE_API_URL` (tambahkan `/api`). URL Quick Tunnel berubah ketika proses dihentikan.

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

Daftar request siap-uji tersedia pada koleksi Postman di `postman/collections/SMP LMS API-1`.
