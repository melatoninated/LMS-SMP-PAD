<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('auth/student/login', [AuthController::class, 'studentLogin'])->middleware('throttle:5,1');
Route::post('auth/parent/login', [AuthController::class, 'parentLogin'])->middleware('throttle:5,1');
Route::post('auth/staff/login', [AuthController::class, 'staffLogin'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::put('auth/password', [AuthController::class, 'updatePassword'])->name('password.update');

    Route::middleware(['force.password', 'role:admin'])->prefix('admin')->group(function () {
        Route::get('dashboard', [AdminController::class, 'dashboard']);
        Route::match(['get', 'post'], 'teachers', [AdminController::class, 'teachers']);
        Route::match(['put', 'delete'], 'teachers/{teacher}', [AdminController::class, 'teacher']);
        Route::match(['get', 'post'], 'students', [AdminController::class, 'students']);
        Route::post('students/import', [AdminController::class, 'importStudents']);
        Route::match(['put', 'delete'], 'students/{student}', [AdminController::class, 'student']);
        Route::post('students/{student}/reset-password', [AdminController::class, 'resetStudentPassword']);
        Route::match(['get', 'post'], 'parents', [AdminController::class, 'parents']);
        Route::match(['put', 'delete'], 'parents/{parent}', [AdminController::class, 'parent']);
        Route::match(['get', 'post'], 'classes', [AdminController::class, 'classes']);
        Route::match(['put', 'delete'], 'classes/{classRoom}', [AdminController::class, 'classRoom']);
        Route::match(['get', 'post'], 'academic-years', [AdminController::class, 'academicYears']);
        Route::match(['put', 'delete'], 'academic-years/{academicYear}', [AdminController::class, 'academicYear']);
        Route::post('academic-years/{academicYear}/activate', [AdminController::class, 'activate']);
        Route::match(['get', 'post'], 'subjects', [AdminController::class, 'subjects']);
        Route::match(['put', 'delete'], 'subjects/{subject}', [AdminController::class, 'subject']);
        Route::match(['get', 'post'], 'teaching-assignments', [AdminController::class, 'teachingAssignments']);
        Route::match(['put', 'delete'], 'teaching-assignments/{teachingAssignment}', [AdminController::class, 'teachingAssignment']);
    });

    Route::middleware(['force.password', 'role:teacher'])->prefix('teacher')->group(function () {
        Route::get('dashboard', [TeacherController::class, 'dashboard']);
        Route::get('courses', [TeacherController::class, 'courses']);
        Route::post('courses', [TeacherController::class, 'storeCourse']);
        Route::get('courses/{course}', [TeacherController::class, 'showCourse']);
        Route::put('courses/{course}', [TeacherController::class, 'updateCourse']);
        Route::delete('courses/{course}', [TeacherController::class, 'destroyCourse']);
        Route::post('courses/{course}/sections', [TeacherController::class, 'storeSection']);
        Route::post('sections/{section}/materials', [TeacherController::class, 'storeMaterial']);
        Route::post('sections/{section}/assessments', [TeacherController::class, 'storeAssessment']);
        Route::post('assessments/{assessment}/questions', [TeacherController::class, 'storeQuestion']);
        Route::get('submissions', [TeacherController::class, 'submissions']);
        Route::post('submissions/{submission}/grade', [TeacherController::class, 'grade']);
    });

    Route::middleware(['force.password', 'role:student'])->prefix('student')->group(function () {
        Route::get('dashboard', [StudentController::class, 'dashboard']);
        Route::get('courses', [StudentController::class, 'courses']);
        Route::post('courses/{course}/enroll', [StudentController::class, 'enroll']);
        Route::get('courses/{course}', [StudentController::class, 'showCourse']);
        Route::post('materials/{material}/complete', [StudentController::class, 'completeMaterial']);
        Route::get('assessments/{assessment}', [StudentController::class, 'showAssessment']);
        Route::put('assessments/{assessment}/draft', [StudentController::class, 'saveAssessmentDraft']);
        Route::post('assessments/{assessment}/submit', [StudentController::class, 'submit']);
        Route::post('exam-sessions/{examSession}/logs', [StudentController::class, 'log']);
        Route::get('profile', [StudentController::class, 'profile']);
        Route::put('profile/class', [StudentController::class, 'updateClass']);
        Route::put('profile/profiling', [StudentController::class, 'saveProfiling']);
    });

    Route::middleware(['force.password', 'role:parent'])->get('parent/dashboard', [ParentController::class, 'dashboard']);
});
