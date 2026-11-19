# LMS-SMP data model

This ERD is a documentation view of the current Laravel migrations, including later schema changes for academic years, teaching assignments, soft deletion, assessment drafts, and Sanctum tokens. Migrations remain the schema source of truth.

```mermaid
erDiagram
    USERS ||--o| TEACHERS : has_profile
    USERS ||--o| STUDENTS : has_profile
    USERS ||--o| PARENTS : has_profile
    USERS ||--o{ PERSONAL_ACCESS_TOKENS : owns
    USERS ||--o{ AUDIT_LOGS : creates

    ACADEMIC_YEARS ||--o{ CLASS_ROOMS : contains
    ACADEMIC_YEARS ||--o{ TEACHING_ASSIGNMENTS : applies_to
    ACADEMIC_YEARS ||--o{ COURSES : applies_to
    CLASS_ROOMS ||--o{ STUDENTS : contains
    CLASS_ROOMS ||--o{ TEACHING_ASSIGNMENTS : receives
    CLASS_ROOMS ||--o{ COURSES : applies_to

    PARENTS }o--o{ STUDENTS : parent_student
    PARENTS }o--o| STUDENTS : primary_student
    STUDENTS ||--o{ STUDENT_CLASS_HISTORIES : records
    ACADEMIC_YEARS ||--o{ STUDENT_CLASS_HISTORIES : records
    CLASS_ROOMS ||--o{ STUDENT_CLASS_HISTORIES : records

    TEACHERS ||--o{ TEACHING_ASSIGNMENTS : receives
    SUBJECTS ||--o{ TEACHING_ASSIGNMENTS : defines
    TEACHERS ||--o{ COURSES : owns
    TEACHING_ASSIGNMENTS ||--o{ COURSES : creates
    COURSES }o--o{ STUDENTS : course_student
    COURSES ||--o{ SECTIONS : contains
    SECTIONS ||--o{ MATERIALS : contains
    SECTIONS ||--o{ ASSESSMENTS : contains
    MATERIALS }o--o{ STUDENTS : material_progress

    ASSESSMENTS ||--o{ QUESTIONS : contains
    ASSESSMENTS ||--o{ SUBMISSIONS : receives
    ASSESSMENTS ||--o{ EXAM_SESSIONS : starts
    ASSESSMENTS ||--o{ ASSESSMENT_DRAFTS : has
    QUESTIONS ||--o{ QUESTION_OPTIONS : has
    QUESTIONS ||--o{ ANSWERS : receives
    STUDENTS ||--o{ SUBMISSIONS : submits
    STUDENTS ||--o{ EXAM_SESSIONS : takes
    STUDENTS ||--o{ ASSESSMENT_DRAFTS : saves
    SUBMISSIONS ||--o{ ANSWERS : contains
    SUBMISSIONS ||--o| GRADES : receives
    TEACHERS ||--o{ GRADES : gives
    EXAM_SESSIONS ||--o{ EXAM_ACTIVITY_LOGS : records
    STUDENTS ||--o{ PROFILING_ANSWERS : provides
    PROFILING_QUESTIONS ||--o{ PROFILING_ANSWERS : defines

    USERS {
        bigint id PK
        string username "unique with role"
        enum role
        bool must_change_password
        bool is_active
        bool is_deleted
    }
    ACADEMIC_YEARS {
        bigint id PK
        string name UK
        int start_year UK
        bool is_active
        bool is_deleted
    }
    CLASS_ROOMS {
        bigint id PK
        bigint academic_year_id FK
        string name UK
        int grade_level
        bool is_deleted
    }
    STUDENTS {
        bigint id PK
        bigint user_id FK
        bigint class_room_id FK
        string nisn UK
        date birth_date
        int cohort_year
        bool is_deleted
    }
    PARENTS {
        bigint id PK
        bigint user_id FK
        bigint student_id FK "unique primary child"
        bool is_deleted
    }
    TEACHERS {
        bigint id PK
        bigint user_id FK
        string employee_no UK
        bool is_deleted
    }
    SUBJECTS {
        bigint id PK
        string code UK
        string name UK
        bool is_deleted
    }
    TEACHING_ASSIGNMENTS {
        bigint id PK
        bigint teacher_id FK
        bigint subject_id FK
        bigint academic_year_id FK
        bigint class_room_id FK
        int grade_level
        bool is_deleted
    }
    COURSES {
        bigint id PK
        bigint teacher_id FK
        bigint teaching_assignment_id FK
        bigint academic_year_id FK
        bigint class_room_id FK
        int target_grade
        bool is_deleted
    }
    ASSESSMENT_DRAFTS {
        bigint id PK
        bigint assessment_id FK
        bigint student_id FK
        json answers
    }
    EXAM_SESSIONS {
        bigint id PK
        bigint assessment_id FK
        bigint student_id FK
        int attempt_no
        datetime expires_at
        enum status
    }
```

## Schema notes

- `users.username` is unique together with `users.role`, allowing a student and that student's parent to use the same NISN while remaining separate accounts.
- Application-level soft deletion uses `is_deleted` on users, academic years, classes, teachers, parents, students, courses, subjects, and teaching assignments. These are not Laravel `deleted_at` soft deletes.
- `parents.student_id` is a unique primary-child relation used by the current parent dashboard. The `parent_student` pivot remains in the schema.
- `courses.teaching_assignment_id`, `academic_year_id`, and `class_room_id` are nullable for compatibility with existing courses; new teacher-created courses use a teaching assignment.
- `exam_sessions` use an attempt number and the server-side `expires_at` timestamp. `assessment_drafts` stores answer JSON for the automatic expired-exam submission process.
