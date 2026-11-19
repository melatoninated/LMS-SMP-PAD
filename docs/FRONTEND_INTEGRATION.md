# Next.js/Vercel integration and Wasmer deployment

This guide connects an independently deployed Next.js frontend to the LMS-SMP Laravel API. It is based on the checked-in Laravel and Wasmer configuration. Statements about runtime behavior are marked **Requires verification** unless they can be established from source files.

## API base URL

Use exactly one `/api` segment. Set this Vercel environment variable for the production frontend:

```env
NEXT_PUBLIC_API_URL=https://lms-smp-backend.wasmer.app/api
```

Do not append `/api` again when building endpoint paths. For example, `${baseUrl}/auth/login` is correct. `NEXT_PUBLIC_*` variables are visible to browser code: never put passwords, database values, `APP_KEY`, or tokens in them.

For a local backend, use `http://127.0.0.1:8000/api` instead.

## Authentication

The API uses Laravel Sanctum personal-access tokens, sent in an `Authorization: Bearer <token>` header. There is no registration endpoint in `routes/api.php`.

| Endpoint | Intended role | Request fields |
| --- | --- | --- |
| `POST /auth/login` | Any | `username`, `password`, `role`; optional `remember`, `device_name` |
| `POST /auth/student/login` | Student | `username`, `password`; optional `remember`, `device_name` |
| `POST /auth/parent/login` | Parent | Same as student login |
| `POST /auth/staff/login` | Admin or teacher | `username`, `password`, `role` (`admin` or `teacher`); optional `remember`, `device_name` |
| `GET /me` | Authenticated user | No body |
| `PUT /auth/password` | Authenticated user | `current_password`, `password`, `password_confirmation` |
| `POST /auth/logout` | Authenticated user | No body |

Successful login returns `{ token, token_type, must_change_password, user }`. Send the token on every protected request. If `must_change_password` is `true`, direct the user to password change before calling role endpoints; their API access is restricted by `force.password` middleware.

The token is a credential. Prefer a secure storage strategy appropriate to the frontend threat model; if browser storage is used, protect against XSS and remove the token on logout. This API does not use cross-site session cookies, so `credentials: 'include'` is not required.

## Client examples

### Fetch

```ts
const API_URL = process.env.NEXT_PUBLIC_API_URL;

export async function login(username: string, password: string, role: string) {
  const response = await fetch(`${API_URL}/auth/login`, {
    method: 'POST',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password, role, device_name: 'nextjs-web' }),
  });
  const body = await response.json();
  if (!response.ok) throw body;
  return body as { token: string; must_change_password: boolean; user: unknown };
}

export async function api<T>(path: string, token: string, init: RequestInit = {}) {
  const response = await fetch(`${API_URL}${path}`, {
    ...init,
    headers: { Accept: 'application/json', Authorization: `Bearer ${token}`, ...init.headers },
  });
  if (response.status === 204) return undefined as T;
  const body = await response.json();
  if (!response.ok) throw body;
  return body as T;
}
```

### Upload

Do not set `Content-Type` manually for `FormData`; the browser supplies the multipart boundary.

```ts
const form = new FormData();
form.append('title', 'Linear equations');
form.append('type', 'pdf');
form.append('target_grade', '7');
form.append('file', selectedFile);

await api('/teacher/sections/12/materials', token, { method: 'POST', body: form });
```

For assessment answer files, submit to `POST /student/assessments/{assessmentId}/submit` and append each file as `files[{questionId}]`. See [API_CONTRACT.md](API_CONTRACT.md) for every endpoint and field.

## Error handling

Always check the HTTP status before using a response. Laravel validation errors use status `422` and normally contain `message` and per-field `errors`:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "username": ["The username field is required."]
  }
}
```

Expected status handling:

| Status | Meaning | Frontend action |
| --- | --- | --- |
| `401` | Missing, expired, or invalid token | Clear token and return to login |
| `403` | Role/ownership/business rule denies the action | Show a permission or rule message; do not retry automatically |
| `404` | Route or model was not found | Refresh stale resource state or show not found |
| `409` | An assessment attempt was already submitted | Refresh the assessment/submission state |
| `422` | Validation or domain validation failed | Render `errors` next to inputs when available |
| `500` | Unexpected server failure | Show a generic failure; never expose server details to users |

The exact text of framework-generated errors can vary with Laravel locale/configuration. API consumers should use status codes and field keys rather than matching messages.

## CORS

`config/cors.php` applies CORS to `api/*`, allows all methods and request headers, exposes no special headers, and sets `supports_credentials` to `false`. Permitted origins come from the comma-separated backend variable `CORS_ALLOWED_ORIGINS`.

Before the production Vercel frontend can call the API, configure the exact Vercel production origin in Wasmer, for example:

```env
CORS_ALLOWED_ORIGINS=https://your-frontend.vercel.app
```

Use an origin only (scheme and host): no `/api`, path, or trailing slash. Multiple origins are comma-separated. Source code confirms this configuration mechanism, but successful production CORS behavior is **Requires verification** against the final Vercel domain. Preview deployments have different domains and must be added explicitly if they need API access.

## File storage

The Laravel `public` disk stores files in `storage/app/public`; `FILESYSTEM_DISK=public` selects it. Material uploads are stored in `materials/`; uploaded assessment answers are stored in `assessment-answers/`. The API returns stored relative paths, not a guaranteed absolute public URL. Constructing a browser URL requires that `public/storage` correctly links to the disk and that `APP_URL` is the backend public URL.

The Wasmer manifest declares the `lms-uploads` persistent volume mounted at `/app/storage/app/public`. This is configuration evidence only. Public-link availability and persistence after a real upload require verification after deployment.

Allowed upload policy enforced by `UploadPolicy`:

| Category | Extensions | Maximum size |
| --- | --- | --- |
| Image | `jpg`, `jpeg`, `png`, `gif`, `webp` | 5 MB |
| PDF | `pdf` | 10 MB |
| Audio | `mp3`, `wav`, `m4a`, `aac`, `ogg` | 15 MB |
| Video | `mp4`, `mov`, `avi`, `webm`, `mkv` | 50 MB |

The Laravel validation layer also caps a submitted material/answer file at 50 MB. On Wasmer, `wasmer-config/php.ini` sets `upload_max_filesize=55M` and `post_max_size=60M`.

## Wasmer deployment and database

The checked-in manifest identifies an existing Wasmer application:

| Item | Configured value |
| --- | --- |
| Application | `aureliusdavian/lms-smp-backend` |
| Public URL | `https://lms-smp-backend.wasmer.app` |
| Region | `fr-roub1` |
| Runtime | `php/php-64` `=8.3.2102`, WASIX runner |
| Server command | PHP serves `/app/public` on `0.0.0.0:8080` |
| PHP configuration | `PHPRC=/config/`, mounted from `wasmer-config` |
| Database capability | Wasmer managed MySQL |
| Upload volume | `lms-uploads` at `/app/storage/app/public` |

`app.yaml` configures the Wasmer application/capabilities; `wasmer.toml` configures the PHP package and entry command. They should be changed only when deployment requirements change.

Laravel reads the MySQL database name from `DB_DATABASE`, with `DB_NAME` only as a fallback. Configure secrets/environment variables in Wasmer, not in Git:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=<generated-secret>
APP_URL=https://lms-smp-backend.wasmer.app
DB_CONNECTION=mysql
DB_HOST=<managed-host>
DB_PORT=<managed-port>
DB_DATABASE=<managed-database-name>
DB_USERNAME=<managed-user>
DB_PASSWORD=<managed-password>
FILESYSTEM_DISK=public
CORS_ALLOWED_ORIGINS=https://your-frontend.vercel.app
```

Do not put any backend secret in a Vercel `NEXT_PUBLIC_*` variable, and never connect the browser directly to MySQL.

### Migrations and seeders without application SSH

The reported deployment context states that production migrations were run from a local computer using environment variables targeting Wasmer MySQL. The source code cannot independently prove that history. If an approved operator must run future migrations without application SSH:

1. Obtain the current managed MySQL connection values through the approved Wasmer secret/database workflow.
2. Set those values only in the operator's process environment or a protected, untracked local configuration.
3. Confirm the target database and take the normal operational backup/maintenance precautions.
4. Run `php artisan migrate --force` from the repository root.
5. Verify migration status with `php artisan migrate:status` against the same target.

`php artisan db:seed --force` is a separate data-write operation. Run it only when initial/demo data is intentionally needed and the seeder's idempotent behavior has been reviewed. Never use `migrate:fresh` on the managed production database.

### Deployment workflow and verification

The reported Wasmer application status and manual/package deployment history are external information, not verifiable from repository files. GitHub automatic deployment is **not verified** and should not be assumed; the reported OAuth redirect error remains an external configuration issue.

For an approved manual release, review source/configuration, deploy through the authorized Wasmer workflow, then verify `/up`, API reachability, database access, CORS, login, public storage, upload persistence, and scheduler operation. This documentation task does not deploy, migrate, seed, or change Wasmer secrets.

## Release checklist

- [ ] `NEXT_PUBLIC_API_URL` is `https://lms-smp-backend.wasmer.app/api` with no trailing slash.
- [ ] Wasmer has `APP_DEBUG=false`, a secret `APP_KEY`, MySQL variables, and `FILESYSTEM_DISK=public`.
- [ ] `CORS_ALLOWED_ORIGINS` includes the exact final Vercel origin.
- [ ] Managed MySQL migration status is confirmed; seeding was performed only if intended.
- [ ] `/up`, login, role access, CORS, and upload persistence have been tested against production.
- [ ] A scheduler/worker arrangement is confirmed for the every-minute expired-exam command.
