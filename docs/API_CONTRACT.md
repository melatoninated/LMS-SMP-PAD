# LMS-SMP API contract

Source of truth: `routes/api.php` and `app/Http/Controllers/Api`. Base URL is `{BASE_URL}/api` (`https://lms-smp-backend.wasmer.app/api` in production). Send `Accept: application/json` for every request. JSON is used unless an endpoint accepts a file.

## Cross-cutting rules

- Protected endpoints require `Authorization: Bearer {token}`. Tokens are issued by Laravel Sanctum.
- All role routes also use `force.password`. A user whose `must_change_password` is true must first call `PUT /auth/password`; `GET /me` and logout remain available.
- `POST /auth/*/login` routes are throttled to five requests per minute.
- A login token expires after one day unless `remember: true` is supplied; the latter creates a token without an application-set expiry.
- List and detail responses normally use `{ "data": ... }`; create requests use `201`; no-content actions use `204`. Lists are not paginated.
- Standard failures are `401` (unauthenticated), `403` (forbidden or business rule), `404` (route/model not found), `409` (duplicate submitted attempt), and `422` (validation). Error message text is not a stable contract.
- Model objects are serialised by Eloquent. Clients must tolerate standard attributes such as `id`, `created_at`, and `updated_at` in addition to documented relationships.

## Authentication and current user

| Method | Endpoint | Auth / role | Request body | Success response |
| --- | --- | --- | --- | --- |
| POST | `/auth/login` | Public | `username` string, `password` string, `role`: `admin`, `student`, `teacher`, or `parent`; optional `remember` boolean, `device_name` string (max 100) | `200` login object |
| POST | `/auth/student/login` | Public | `username`, `password`; optional `remember`, `device_name` | `200` login object; student role forced |
| POST | `/auth/parent/login` | Public | Same as student login | `200` login object; parent role forced |
| POST | `/auth/staff/login` | Public | `username`, `password`, `role`: `admin` or `teacher`; optional `remember`, `device_name` | `200` login object |
| GET | `/me` | Token | — | `200` `{ "user": user }` |
| PUT | `/auth/password` | Token | `current_password`, `password` (min 8), `password_confirmation` | `200` `{ "message": "Password berhasil diubah." }` |
| POST | `/auth/logout` | Token | — | `204`; deletes only the current token |

Login example:

```json
{
  "username": "T001",
  "password": "example-password",
  "role": "teacher",
  "device_name": "nextjs-web",
  "remember": false
}
```

Successful login shape:

```json
{
  "token": "1|sanctum-token-value",
  "token_type": "Bearer",
  "must_change_password": false,
  "user": {
    "id": 8,
    "name": "Example Teacher",
    "username": "T001",
    "email": "teacher@example.test",
    "role": "teacher",
    "must_change_password": false,
    "is_active": true,
    "student": null,
    "teacher": { "id": 2, "user_id": 8, "employee_no": "T001" },
    "parent_profile": null
  }
}
```

## Admin API

All endpoints below require an authenticated `admin`. `DELETE` endpoints perform the soft-delete behavior implemented by the controller and return `204`.

| Method | Endpoint | Purpose | Request fields / constraints | Response |
| --- | --- | --- | --- | --- |
| GET | `/admin/dashboard` | Dashboard counts and alerts | — | `data` with counts, `active_year`, classes, alerts |
| GET | `/admin/teachers` | List active teachers | — | `data`: teachers with `user` |
| POST | `/admin/teachers` | Create teacher and user | `name`, unique email, `password` + confirmation (min 8), unique `employee_no`; optional `phone` | `201` `data`: teacher with user |
| PUT | `/admin/teachers/{teacher}` | Update teacher | `name`, unique `email`, unique `employee_no`; optional `phone` | `data`: updated teacher with user |
| DELETE | `/admin/teachers/{teacher}` | Soft-delete teacher/user | — | `204` |
| GET | `/admin/students` | List active students | — | `data`: students with user and class |
| POST | `/admin/students` | Create student/user | `name`, unique 10-digit `nisn`, `birth_date`, `cohort_year`; optional existing `class_room_id` | `201` `data`: student with user/class |
| PUT | `/admin/students/{student}` | Update student | `name`, unique 10-digit `nisn`, `birth_date` (`Y-m-d`), `cohort_year`; optional `class_room_id` | `data`: updated student |
| DELETE | `/admin/students/{student}` | Soft-delete student/user | — | `204` |
| POST | `/admin/students/{student}/reset-password` | Reset student password | — | `200`; password is birth date formatted `YYYYMMDD` |
| POST | `/admin/students/import` | Import spreadsheet | multipart `file`: required XLSX, max 10 MB | `201` `{message, imported}`; `422` may include `failures` |
| GET | `/admin/parents` | List active parent profiles | — | `data`: parent, user, student |
| POST | `/admin/parents` | Create parent/user | `name`, existing unique `student_id`; optional `phone`, `address` | `201` `data`: parent with user/student |
| PUT | `/admin/parents/{parent}` | Update parent | `name`, existing unique `student_id`; optional `phone`, `address` | `data`: updated parent |
| DELETE | `/admin/parents/{parent}` | Soft-delete parent/user | — | `204` |
| GET | `/admin/classes` | List active classes | — | `data`: classes with `students_count` |
| POST | `/admin/classes` | Create class | unique `name`, `grade_level` 7/8/9, existing `academic_year_id` | `201` `data`: class |
| PUT | `/admin/classes/{classRoom}` | Update class | unique `name`, `grade_level` 7/8/9, existing `academic_year_id`; optional boolean `is_active` | `data`: class |
| DELETE | `/admin/classes/{classRoom}` | Soft-delete class | — | `204` |
| GET | `/admin/academic-years` | List active records | — | `data`: academic years |
| POST | `/admin/academic-years` | Create academic year | unique `name`, unique integer `start_year` | `201` `data`: year |
| PUT | `/admin/academic-years/{academicYear}` | Update academic year | unique `name` matching `YYYY/YYYY`, unique `start_year` | `data`: year |
| DELETE | `/admin/academic-years/{academicYear}` | Soft-delete year | — | `204` |
| POST | `/admin/academic-years/{academicYear}/activate` | Activate year | — | `data`: active year, `message` |
| GET | `/admin/subjects` | List active subjects | — | `data`: subjects |
| POST | `/admin/subjects` | Create subject | unique `code` (max 50), unique `name` (max 255) | `201` `data`: subject |
| PUT | `/admin/subjects/{subject}` | Update subject | unique `code`, unique `name` | `data`: subject |
| DELETE | `/admin/subjects/{subject}` | Soft-delete subject | — | `204` |
| GET | `/admin/teaching-assignments` | List assignments | — | `data`: assignment with teacher/user, subject, year, class |
| POST | `/admin/teaching-assignments` | Create assignment | existing `teacher_id`, `subject_id`, `academic_year_id`, `class_room_id`; `grade_level` 7/8/9 | `201` `data`: assignment |
| PUT | `/admin/teaching-assignments/{teachingAssignment}` | Update assignment | Same required fields as POST | `data`: assignment |
| DELETE | `/admin/teaching-assignments/{teachingAssignment}` | Soft-delete assignment | — | `204` |

For teaching assignments, the selected class must match the submitted grade and academic year; otherwise the controller returns `422`.

## Teacher API

All endpoints require an authenticated `teacher`. Course, section, assessment, and submission policies restrict access to the owning teacher.

| Method | Endpoint | Purpose | Request fields / constraints | Response |
| --- | --- | --- | --- | --- |
| GET | `/teacher/dashboard` | Teacher summary | — | `data`: courses, `need_grading`, alerts |
| GET | `/teacher/courses` | List owned courses | — | `data`: courses with `students_count` |
| POST | `/teacher/courses` | Create course from assignment | Required `teaching_assignment_id` owned by teacher; optional `name`, `description` | `201` `data`: course |
| GET | `/teacher/courses/{course}` | Course authoring detail | — | `data`: course with sections, materials, assessments, questions/options |
| PUT | `/teacher/courses/{course}` | Update course | `name`; optional `description`, boolean `is_active` | `data`: course |
| DELETE | `/teacher/courses/{course}` | Soft-delete course | — | `204` |
| POST | `/teacher/courses/{course}/sections` | Add section | `title` (max 255) | `201` `data`: section; position assigned by server |
| POST | `/teacher/sections/{section}/materials` | Add material | `title`, `type` (`text`, `pdf`, `video`, `audio`, `file`), `target_grade` 7/8/9; optional `body`, `file_path`, `file` | `201` `data`: material |
| POST | `/teacher/sections/{section}/assessments` | Add assessment | See assessment fields below | `201` `data`: assessment |
| POST | `/teacher/assessments/{assessment}/questions` | Add question | See question fields below | `201` `data`: question with options |
| GET | `/teacher/submissions` | List owned-course submissions | — | `data`: submissions with student, assessment/course, answers/questions |
| POST | `/teacher/submissions/{submission}/grade` | Manually grade submission | `score` numeric 0–100 and no greater than total question points; optional `feedback` | `data`: refreshed submission |

Material requests containing `file` must be `multipart/form-data`. A non-text material requires either a validated `file` or `file_path`. A PDF/audio/video upload must match the selected type; generic `file` material can use any type allowed by upload policy.

Assessment request fields: `title`, `assessment_type` (`practice`, `midterm`, `final`, `daily_exam`), `target_grade` (7/8/9), boolean `is_published`, `duration_minutes` (integer >=1), and `end_at` (date after now and after `start_at` if supplied). Optional fields are `description`, boolean `is_exam`, `require_fullscreen`, `anti_copy_paste`, `start_at`, `maximum_attempt` (integer >=1), and `passing_score` (numeric >=0).

Question request fields: `type` (`multiple_choice`, `true_false`, `essay`, `file_upload`), `prompt`, `points` (0–100), optional `correct_answer`, and optional `options`. Each option has `text` and optional boolean `is_correct`. Multiple-choice questions need at least two options and exactly one correct option. True/false questions require `correct_answer` equal to `true` or `false`.

## Student API

All endpoints require an authenticated `student`. Course and assessment access is checked against the student's permitted grade/year and applicable business rules.

| Method | Endpoint | Purpose | Request fields / constraints | Response |
| --- | --- | --- | --- | --- |
| GET | `/student/dashboard` | Student landing data | — | `data`: student/class, expected grade, class-update flag, visible courses |
| GET | `/student/courses` | List accessible courses | — | `data`: courses; `expected_grade` |
| POST | `/student/courses/{course}/enroll` | Enrol in accessible active course | — | `200` `{message}` |
| GET | `/student/courses/{course}` | Course detail | — | `data`: materials and published assessments; includes `is_read_only` for prior year |
| POST | `/student/materials/{material}/complete` | Mark material complete | — | `200` `{message}` |
| GET | `/student/assessments/{assessment}` | Get assessment / start exam | — | `data`: `assessment`, `can_attempt`, `exam_session`, `remaining_seconds` |
| PUT | `/student/assessments/{assessment}/draft` | Save answer draft | Required `answers` object | `204` |
| POST | `/student/assessments/{assessment}/submit` | Submit answers | Optional `answers` object and `files` array | `201` `data`: submission |
| POST | `/student/exam-sessions/{examSession}/logs` | Log exam event | `event` (max 100); optional `detail` | `204` |
| GET | `/student/profile` | Class and profiling data | — | `data`: available classes, profiling questions, recommendations |
| PUT | `/student/profile/class` | Change class | Required existing `class_room_id` | `data`: student with class |
| PUT | `/student/profile/profiling` | Save profiling answers | Required `answers` object; every value integer 1–5 | `200` `{message}` |

The assessment response deliberately hides the `is_correct` field from question options. Opening an exam starts or resumes a server-side exam session when an attempt is allowed. Use `remaining_seconds` only for display; expiration, attempts, and submissions are enforced by the server. Normal submission after a session expires is rejected. File answers use `multipart/form-data` with keys such as `files[42]`; text answers are keyed by question ID:

```json
{
  "answers": {
    "40": "15",
    "41": "true",
    "42": "Explanation for an essay answer"
  }
}
```

`POST /student/exam-sessions/{examSession}/logs` accepts only the student's own active, unexpired session. A class update must select an active class matching the computed expected grade and active academic year.

## Parent API

| Method | Endpoint | Auth / role | Purpose | Response |
| --- | --- | --- | --- | --- |
| GET | `/parent/dashboard` | Authenticated `parent` | View linked child's course progress and submissions | `data`: `student`, `course_progress`, `submissions`, `alerts` |

The current implementation reads one `student` relation from the parent profile. Frontend UI should not assume multi-child support from this endpoint.

## Upload contract

| Endpoint | Multipart field | Stored disk/path | Allowed extension and maximum |
| --- | --- | --- | --- |
| `POST /teacher/sections/{section}/materials` | `file` | `public/materials` | Images: jpg/jpeg/png/gif/webp, 5 MB; PDF: pdf, 10 MB; audio: mp3/wav/m4a/aac/ogg, 15 MB; video: mp4/mov/avi/webm/mkv, 50 MB |
| `POST /student/assessments/{assessment}/submit` | `files[{questionId}]` | `public/assessment-answers` | Same policy; Laravel field validation also caps each submitted file at 50 MB |

Stored paths are relative storage paths, for example `materials/abc.pdf`; they are not API-generated absolute URLs. Serving them depends on the `public/storage` link and configured `APP_URL`.

## Error examples

Validation failures are returned by Laravel as `422`:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "password": ["The password field confirmation does not match."]
  }
}
```

Invalid login credentials are explicitly returned as:

```json
{ "message": "Kredensial tidak valid" }
```

This contract does not invent a custom body for framework-generated `401`, `403`, `404`, or `500` responses; clients must branch on the HTTP status and display a safe generic message when no structured JSON is available.
