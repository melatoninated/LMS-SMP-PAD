<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Course;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AcademicYearService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard() { return response()->json(['data' => ['students' => Student::count(), 'teachers' => Teacher::count(), 'parents' => ParentProfile::count(), 'courses' => Course::count(), 'active_year' => AcademicYear::where('is_active', true)->first(), 'classes' => ClassRoom::withCount('students')->get()]]); }
    public function teachers(Request $request) { if ($request->isMethod('get')) return response()->json(['data' => Teacher::with('user')->get()]); $data = $request->validate(['name' => ['required'], 'email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', 'confirmed', 'min:8'], 'employee_no' => ['nullable', 'unique:teachers,employee_no'], 'phone' => ['nullable']]); $user = User::create(['name' => $data['name'], 'username' => $data['email'], 'email' => $data['email'], 'password' => $data['password'], 'role' => 'teacher', 'must_change_password' => true]); return response()->json(['data' => Teacher::create(['user_id' => $user->id, 'employee_no' => $data['employee_no'] ?? null, 'phone' => $data['phone'] ?? null])->load('user')], 201); }
    public function students(Request $request) { if ($request->isMethod('get')) return response()->json(['data' => Student::with(['user', 'classRoom'])->get()]); $data = $request->validate(['name' => ['required'], 'nisn' => ['required', 'unique:students,nisn'], 'birth_date' => ['required', 'date'], 'password' => ['required', 'confirmed', 'min:8'], 'cohort_year' => ['required', 'integer'], 'class_room_id' => ['nullable', 'exists:class_rooms,id']]); $user = User::create(['name' => $data['name'], 'username' => $data['nisn'], 'password' => $data['password'], 'role' => 'student', 'must_change_password' => true]); unset($data['name'], $data['password'], $data['password_confirmation']); return response()->json(['data' => Student::create($data + ['user_id' => $user->id])->load(['user', 'classRoom'])], 201); }
    public function resetStudentPassword(Request $request, Student $student) { $data = $request->validate(['password' => ['required', 'confirmed', 'min:8']]); $student->user->update(['password' => $data['password'], 'must_change_password' => true]); return response()->json(['message' => 'Password siswa berhasil direset.']); }
    public function parents(Request $request) { if ($request->isMethod('get')) return response()->json(['data' => ParentProfile::with('user', 'students.user')->get()]); $data = $request->validate(['name' => ['required'], 'username' => ['required', 'unique:users,username'], 'password' => ['required', 'confirmed', 'min:8'], 'phone' => ['nullable'], 'address' => ['nullable'], 'student_ids' => ['array'], 'student_ids.*' => ['exists:students,id']]); $user = User::create(['name' => $data['name'], 'username' => $data['username'], 'password' => $data['password'], 'role' => 'parent', 'must_change_password' => true]); $parent = ParentProfile::create(['user_id' => $user->id, 'phone' => $data['phone'] ?? null, 'address' => $data['address'] ?? null]); $parent->students()->sync($data['student_ids'] ?? []); return response()->json(['data' => $parent->load('user', 'students.user')], 201); }
    public function classes(Request $request) { if ($request->isMethod('get')) return response()->json(['data' => ClassRoom::withCount('students')->orderBy('grade_level')->orderBy('name')->get()]); $class = ClassRoom::create($request->validate(['name' => ['required', 'unique:class_rooms,name'], 'grade_level' => ['required', 'integer', 'in:7,8,9'], 'academic_year_id' => ['nullable', 'exists:academic_years,id']])); return response()->json(['data' => $class], 201); }
    public function academicYears(Request $request) { if ($request->isMethod('get')) return response()->json(['data' => AcademicYear::orderByDesc('start_year')->get()]); return response()->json(['data' => AcademicYear::create($request->validate(['name' => ['required', 'unique:academic_years,name'], 'start_year' => ['required', 'integer', 'unique:academic_years,start_year']]))], 201); }
    public function activate(AcademicYear $academicYear, AcademicYearService $service) { $service->activate($academicYear); return response()->json(['data' => $academicYear->fresh(), 'message' => 'Tahun ajaran aktif diperbarui.']); }
}
