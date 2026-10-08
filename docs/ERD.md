# ERD SMP LMS

ERD ini dibuat dari migration Laravel:

- `database/migrations/0001_01_01_000000_create_users_table.php`
- `database/migrations/2026_09_03_000001_create_lms_tables.php`

```mermaid

    USERS ||--o| TEACHERS : has_profile
    USERS ||--o| STUDENTS : has_profile
    USERS ||--o| PARENTS : has_profile
    USERS ||--o{ SESSIONS : owns
    USERS ||--o{ AUDIT_LOGS : creates
erDiagram
    CLASS_ROOMS ||--o{ STUDENTS : contains
    PARENTS }o--o{ STUDENTS : monitors

    TEACHERS ||--o{ COURSES : owns
    TEACHERS ||--o{ GRADES : grades
    COURSES }o--o{ STUDENTS : enrolled_by
    COURSES ||--o{ SECTIONS : contains

    SECTIONS ||--o{ MATERIALS : contains
    SECTIONS ||--o{ ASSESSMENTS : contains

    MATERIALS }o--o{ STUDENTS : completed_by

    ASSESSMENTS ||--o{ QUESTIONS : contains
    ASSESSMENTS ||--o{ SUBMISSIONS : receives
    ASSESSMENTS ||--o{ EXAM_SESSIONS : schedules

    QUESTIONS ||--o{ QUESTION_OPTIONS : has_options
    QUESTIONS ||--o{ ANSWERS : answered_in

    STUDENTS ||--o{ SUBMISSIONS : submits
    STUDENTS ||--o{ EXAM_SESSIONS : takes
    STUDENTS ||--o{ PROFILING_ANSWERS : fills

    SUBMISSIONS ||--o{ ANSWERS : contains
    SUBMISSIONS ||--o| GRADES : receives

    EXAM_SESSIONS ||--o{ EXAM_ACTIVITY_LOGS : records

    PROFILING_QUESTIONS ||--o{ PROFILING_ANSWERS : answered_by

    USERS {
        bigint id PK
        string name
        string username UK
        string email UK
        string password
        enum role
        bool must_change_password
        bool is_active
        string remember_token
        datetime created_at
        datetime updated_at
    }

    SESSIONS {
        string id PK
        bigint user_id FK
        string ip_address
        text user_agent
        text payload
        int last_activity
    }

    ACADEMIC_YEARS {
        bigint id PK
        string name UK
        int start_year UK
        bool is_active
        datetime created_at
        datetime updated_at
    }

    CLASS_ROOMS {
        bigint id PK
        string name UK
        int grade_level
        bool is_active
        datetime created_at
        datetime updated_at
    }

    TEACHERS {
        bigint id PK
        bigint user_id FK, UK
        string employee_no UK
        string phone
        datetime created_at
        datetime updated_at
    }

    PARENTS {
        bigint id PK
        bigint user_id FK, UK
        string phone
        text address
        datetime created_at
        datetime updated_at
    }

    STUDENTS {
        bigint id PK
        bigint user_id FK, UK
        string nisn UK
        date birth_date
        int cohort_year
        bigint class_room_id FK
        bool is_active
        bool is_graduated
        datetime created_at
        datetime updated_at
    }

    PARENT_STUDENT {
        bigint parent_profile_id PK, FK
        bigint student_id PK, FK
        string relationship
    }

    COURSES {
        bigint id PK
        bigint teacher_id FK
        string name
        text description
        int target_grade
        bool is_active
        datetime created_at
        datetime updated_at
    }

    COURSE_STUDENT {
        bigint course_id PK, FK
        bigint student_id PK, FK
        datetime created_at
        datetime updated_at
    }

    SECTIONS {
        bigint id PK
        bigint course_id FK
        string title
        int position
        datetime created_at
        datetime updated_at
    }

    MATERIALS {
        bigint id PK
        bigint section_id FK
        string title
        enum type
        text body
        string file_path
        int target_grade
        datetime created_at
        datetime updated_at
    }

    MATERIAL_PROGRESS {
        bigint material_id PK, FK
        bigint student_id PK, FK
        datetime completed_at
    }

    ASSESSMENTS {
        bigint id PK
        bigint section_id FK
        string title
        text description
        int target_grade
        bool is_exam
        int duration_minutes
        datetime start_at
        datetime end_at
        int maximum_attempt
        decimal passing_score
        datetime created_at
        datetime updated_at
    }

    QUESTIONS {
        bigint id PK
        bigint assessment_id FK
        enum type
        text prompt
        decimal points
        text correct_answer
        int position
        datetime created_at
        datetime updated_at
    }

    QUESTION_OPTIONS {
        bigint id PK
        bigint question_id FK
        string option_text
        bool is_correct
        datetime created_at
        datetime updated_at
    }

    SUBMISSIONS {
        bigint id PK
        bigint assessment_id FK
        bigint student_id FK
        int attempt_no
        datetime submitted_at
        decimal score
        enum status
        text feedback
        datetime created_at
        datetime updated_at
    }

    ANSWERS {
        bigint id PK
        bigint submission_id FK
        bigint question_id FK
        text answer_text
        string file_path
        bool is_correct
        decimal points_awarded
        datetime created_at
        datetime updated_at
    }

    GRADES {
        bigint id PK
        bigint submission_id FK, UK
        bigint teacher_id FK
        decimal score
        string status
        text feedback
        datetime graded_at
        datetime created_at
        datetime updated_at
    }

    EXAM_SESSIONS {
        bigint id PK
        bigint assessment_id FK
        bigint student_id FK
        datetime started_at
        datetime expires_at
        datetime submitted_at
        enum status
        datetime created_at
        datetime updated_at
    }

    EXAM_ACTIVITY_LOGS {
        bigint id PK
        bigint exam_session_id FK
        string event
        text detail
        datetime created_at
        datetime updated_at
    }

    PROFILING_QUESTIONS {
        bigint id PK
        string code UK
        text prompt
        string material_keyword
        datetime created_at
        datetime updated_at
    }

    PROFILING_ANSWERS {
        bigint id PK
        bigint profiling_question_id FK
        bigint student_id FK
        int answer_value
        datetime created_at
        datetime updated_at
    }

    AUDIT_LOGS {
        bigint id PK
        bigint user_id FK
        string event
        string entity_type
        bigint entity_id
        text detail
        datetime created_at
    }
```

## Catatan Relasi

- `users.role` menentukan akses utama: `admin`, `teacher`, `student`, atau `parent`.
- `teachers`, `students`, dan `parents` adalah profile table yang terhubung 1:1 ke `users`.
- `parent_student`, `course_student`, dan `material_progress` adalah pivot table.
- `academic_years` tidak memiliki FK langsung ke siswa karena tingkat kelas dihitung dari `students.cohort_year` dan tahun ajaran aktif.
- `exam_sessions.expires_at` menjadi sumber kebenaran timer ujian di server.
- `audit_logs.entity_type` dan `entity_id` bersifat polymorphic ringan untuk mencatat perubahan data penting.
