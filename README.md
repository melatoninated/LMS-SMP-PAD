# LMS-SMP Backend

Laravel backend for the LMS-SMP learning management system. It provides the server-rendered school interface and a Sanctum token API consumed by a separately deployed Next.js frontend. The production backend is hosted on Wasmer Edge at `https://lms-smp-backend.wasmer.app`.

## Technology stack

- Laravel 12 and PHP 8.3 (the Composer platform target and Wasmer package runtime)
- Laravel Sanctum personal-access tokens
- MySQL for Wasmer production; SQLite is the default local configuration
- Composer, Vite, Tailwind CSS, and Axios
- Wasmer Edge with the PHP WASIX runtime
- An external Next.js frontend, typically deployed on Vercel

## Architecture

- `routes/web.php` contains session-authenticated Blade routes; `routes/api.php` contains the `/api` token API.
- `app/Http/Controllers/Api` implements role-scoped API operations. Laravel request validation is performed in controllers; no Form Request classes or API Resource classes are present.
- `EnsureRole` and `ForcePasswordChange` enforce role access and initial-password changes. Sanctum protects authenticated API routes.
- Eloquent models and migrations define the LMS domain: academic years, classes, profiles, courses, assessments, exam sessions, submissions, and grades.
- `app/Services` contains assessment, exam, academic-year, and upload rules. The exam-expiration command is scheduled every minute in `routes/console.php`; a production scheduler process still requires operational verification.
- Public uploads use the `public` filesystem disk (`storage/app/public`) and are exposed through `public/storage` after `php artisan storage:link`.

## Repository layout

| Path | Responsibility |
| --- | --- |
| `app/Http/Controllers/Api` | REST API implementation |
| `app/Models`, `app/Services`, `app/Policies` | Domain models, business rules, and authorization |
| `routes/api.php`, `routes/web.php`, `routes/console.php` | API, browser, and scheduled command routes |
| `database/migrations`, `database/seeders` | Schema history and optional demo data |
| `config` | Database, CORS, filesystem, auth, and application configuration |
| `resources` | Blade, CSS, and JavaScript sources |
| `docs/API_CONTRACT.md` | Source-aligned API contract |
| `docs/FRONTEND_INTEGRATION.md` | Next.js/Vercel and Wasmer integration guide |

## Local development

Prerequisites: PHP 8.2 or later, Composer 2, Node.js 20 or later, and npm. The commands below target PowerShell.

```powershell
git clone https://github.com/melatoninated/LMS-SMP-PAD.git
Set-Location LMS-SMP-PAD
composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File -Path database/database.sqlite -Force
php artisan migrate
# Optional: load demo records
php artisan db:seed
php artisan storage:link
php artisan serve
```

Run `npm run dev` in a second terminal during frontend-asset development, or `npm run build` for a production asset build. The checked-in `.env.example` uses SQLite. For local MySQL, set `DB_CONNECTION=mysql` and the `DB_*` variables before running migrations.

`php artisan migrate` changes only the database selected by the current environment. Do not point local commands at production unintentionally.

## Seed data

`DatabaseSeeder` creates demo data when run, including an administrator, teachers, students, parents, courses, assessments, and profiling questions. It is separate from migrations: migrations create or evolve schema; seeding inserts application data. Seeder execution against the production database is **not verified** and should be an intentional, reviewed operation.

The seed credentials exist only for a newly seeded development database. They are not evidence that those users exist in production, and they should not be used as production credentials.

## API

Local base URL: `http://127.0.0.1:8000/api`.

Production base URL: `https://lms-smp-backend.wasmer.app/api`.

The API uses Laravel Sanctum Bearer tokens. The complete, source-aligned endpoint catalog, request fields, validation rules, response examples, uploads, and error handling are in [API_CONTRACT.md](docs/API_CONTRACT.md). The frontend-specific guide is [FRONTEND_INTEGRATION.md](docs/FRONTEND_INTEGRATION.md).

Routes can be inspected safely with:

```powershell
php artisan route:list --path=api
```

Laravel's configured health endpoint is `GET /up` (outside `/api`). Availability of that endpoint on the deployed application is **not verified** by this repository.

## Wasmer deployment

The checked-in deployment configuration identifies:

- Application: `aureliusdavian/lms-smp-backend` (`app_id: da_R36ILtlUzxyB`)
- Region: `fr-roub1`
- Runtime: `php/php-64` version `8.3.2102`, running as WASIX
- PHP server entry point: `/app/public` on `0.0.0.0:8080`
- PHP configuration: `wasmer-config/php.ini`, supplied through `PHPRC=/config/`
- Managed MySQL capability and persistent `lms-uploads` volume mounted at `/app/storage/app/public`

`app.yaml` declares the Wasmer application, managed database capability, region, scaling mode, and volume. `wasmer.toml` describes the runtime package, mounted application/config paths, and PHP command. Neither file proves a request, CORS, upload, or scheduler flow has been tested in production.

See [FRONTEND_INTEGRATION.md](docs/FRONTEND_INTEGRATION.md#wasmer-deployment-and-database) for safe deployment and migration guidance. GitHub automatic deployment must be treated as **not configured/not verified**: this repository contains no Git deployment configuration, and the reported OAuth connection failure is not resolved here.

## Production configuration and security

Set production values in Wasmer secrets/environment configuration; never commit real credentials, tokens, or `APP_KEY`.

| Variable | Purpose |
| --- | --- |
| `APP_ENV`, `APP_DEBUG`, `APP_KEY`, `APP_URL` | Laravel environment, debug policy, encryption key, and public URL |
| `DB_CONNECTION=mysql` | Selects the MySQL connection |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL connection values |
| `DB_NAME` | Fallback database name only when `DB_DATABASE` is absent in this codebase |
| `FILESYSTEM_DISK=public` | Stores application uploads on the public disk |
| `CORS_ALLOWED_ORIGINS` | Comma-separated frontend origins, without paths or trailing slashes |

For this deployment, use `APP_URL=https://lms-smp-backend.wasmer.app`, `APP_ENV=production`, and `APP_DEBUG=false`. The actual secret values and production connectivity are outside this repository and are not documented here.

## Verification status

Verified from source/configuration: route definitions, Sanctum middleware usage, MySQL environment mapping, public storage path, Wasmer manifest values, and the declared persistent-volume mount.

Requires deployment verification: current Wasmer release status, public API reachability, database connectivity, migrations and seed data present in production, login, CORS from the final Vercel domain, public storage link behavior, upload persistence, scheduler execution, and GitHub auto-deployment.
