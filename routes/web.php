<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ParentDashboardController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\TeacherDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->name('login.perform');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'force.password'])->group(function () {
    Route::get('/password/change', [AuthController::class, 'changePassword'])->name('password.change');
    Route::post('/password/change', [AuthController::class, 'updatePassword'])->name('password.update');
});

Route::middleware(['auth', 'force.password', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::resource('teachers', AdminController::class)->parameters(['teachers' => 'teacher'])->only(['index', 'store']);
    Route::get('/students', [AdminController::class, 'students'])->name('students.index');
    Route::post('/students', [AdminController::class, 'storeStudent'])->name('students.store');
    Route::post('/students/{student}/reset-password', [AdminController::class, 'resetStudentPassword'])->name('students.reset-password');
    Route::get('/parents', [AdminController::class, 'parents'])->name('parents.index');
    Route::post('/parents', [AdminController::class, 'storeParent'])->name('parents.store');
    Route::get('/classes', [AdminController::class, 'classes'])->name('classes.index');
    Route::post('/classes', [AdminController::class, 'storeClass'])->name('classes.store');
    Route::get('/academic-years', [AdminController::class, 'academicYears'])->name('years.index');
    Route::post('/academic-years', [AdminController::class, 'storeAcademicYear'])->name('years.store');
    Route::post('/academic-years/{academicYear}/activate', [AdminController::class, 'activateAcademicYear'])->name('years.activate');
    Route::get('/import/students', [AdminController::class, 'studentImportForm'])->name('students.import.form');
    Route::get('/import/students/template', [AdminController::class, 'studentImportTemplate'])->name('students.import.template');
    Route::post('/import/students', [AdminController::class, 'importStudents'])->name('students.import');
});

Route::middleware(['auth', 'force.password', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/', [TeacherDashboardController::class, 'dashboard'])->name('dashboard');
    Route::resource('courses', CourseController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('/courses/{course}/sections', [CourseController::class, 'storeSection'])->name('sections.store');
    Route::post('/sections/{section}/materials', [CourseController::class, 'storeMaterial'])->name('materials.store');
    Route::post('/sections/{section}/assessments', [AssessmentController::class, 'store'])->name('assessments.store');
    Route::post('/assessments/{assessment}/questions', [AssessmentController::class, 'storeQuestion'])->name('questions.store');
    Route::get('/grades', [TeacherDashboardController::class, 'grades'])->name('grades.index');
    Route::post('/submissions/{submission}/grade', [AssessmentController::class, 'manualGrade'])->name('grades.store');
    Route::get('/grades/export', [TeacherDashboardController::class, 'exportGrades'])->name('grades.export');
});

Route::middleware(['auth', 'force.password', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/', [StudentDashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/courses', [StudentDashboardController::class, 'courses'])->name('courses.index');
    Route::post('/courses/{course}/enroll', [StudentDashboardController::class, 'enroll'])->name('courses.enroll');
    Route::get('/courses/{course}', [StudentDashboardController::class, 'showCourse'])->name('courses.show');
    Route::post('/materials/{material}/complete', [StudentDashboardController::class, 'completeMaterial'])->name('materials.complete');
    Route::get('/assessments/{assessment}', [AssessmentController::class, 'showStudent'])->name('assessments.show');
    Route::post('/assessments/{assessment}/submit', [AssessmentController::class, 'submit'])->name('assessments.submit');
    Route::post('/exams/{examSession}/log', [ExamController::class, 'log'])->name('exams.log');
    Route::get('/profile', [StudentDashboardController::class, 'profile'])->name('profile');
    Route::post('/profile/class', [StudentDashboardController::class, 'updateClass'])->name('profile.class');
    Route::post('/profile/profiling', [StudentDashboardController::class, 'saveProfiling'])->name('profile.profiling');
});

Route::middleware(['auth', 'force.password', 'role:parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/', [ParentDashboardController::class, 'dashboard'])->name('dashboard');
});
