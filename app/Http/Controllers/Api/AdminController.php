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
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        return response()->json(['data' => [
            'students' => Student::count(),
            'teachers' => Teacher::count(),
            'parents' => ParentProfile::count(),
            'courses' => Course::count(),
            'active_year' => AcademicYear::where('is_active', true)->first(),
            'classes' => ClassRoom::withCount('students')->get(),
        ]]);
    }

    public function teachers(Request $request)
    {
        if ($request->isMethod('get')) {
            return response()->json(['data' => Teacher::with('user')->get()]);
        }

        $data = $request->validate([
            'name' => ['required'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'employee_no' => ['required', 'string', 'max:100', 'unique:teachers,employee_no', 'unique:users,username'],
            'phone' => ['nullable'],
        ]);
        $user = User::create([
            'name' => $data['name'],
            'username' => $data['employee_no'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'teacher',
            'must_change_password' => true,
        ]);

        return response()->json(['data' => Teacher::create([
            'user_id' => $user->id,
            'employee_no' => $data['employee_no'],
            'phone' => $data['phone'] ?? null,
        ])->load('user')], 201);
    }

    public function students(Request $request)
    {
        if ($request->isMethod('get')) {
            return response()->json(['data' => Student::with(['user', 'classRoom'])->get()]);
        }

        $data = $request->validate([
            'name' => ['required'],
            'nisn' => ['required', 'digits:10', 'unique:students,nisn', 'unique:users,username'],
            'birth_date' => ['required', 'date'],
            'cohort_year' => ['required', 'integer'],
            'class_room_id' => ['nullable', 'exists:class_rooms,id'],
        ]);
        $user = User::create([
            'name' => $data['name'],
            'username' => $data['nisn'],
            'password' => Carbon::parse($data['birth_date'])->format('dmY'),
            'role' => 'student',
            'must_change_password' => true,
        ]);
        unset($data['name']);

        return response()->json(['data' => Student::create($data + ['user_id' => $user->id])->load(['user', 'classRoom'])], 201);
    }

    public function resetStudentPassword(Student $student)
    {
        $student->user->update(['password' => $student->birth_date->format('dmY'), 'must_change_password' => true]);

        return response()->json(['message' => 'Password siswa direset ke tanggal lahir (DDMMYYYY).']);
    }

    public function parents(Request $request)
    {
        if ($request->isMethod('get')) {
            return response()->json(['data' => ParentProfile::with('user', 'students.user')->get()]);
        }

        $data = $request->validate([
            'name' => ['required'],
            'username' => ['required', 'unique:users,username'],
            'password' => ['required', 'confirmed', 'min:8'],
            'phone' => ['nullable'],
            'address' => ['nullable'],
            'student_ids' => ['array'],
            'student_ids.*' => ['exists:students,id'],
        ]);
        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'password' => $data['password'],
            'role' => 'parent',
            'must_change_password' => true,
        ]);
        $parent = ParentProfile::create([
            'user_id' => $user->id,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
        ]);
        $parent->students()->sync($data['student_ids'] ?? []);

        return response()->json(['data' => $parent->load('user', 'students.user')], 201);
    }

    public function classes(Request $request)
    {
        if ($request->isMethod('get')) {
            return response()->json(['data' => ClassRoom::withCount('students')->orderBy('grade_level')->orderBy('name')->get()]);
        }

        $class = ClassRoom::create($request->validate([
            'name' => ['required', 'unique:class_rooms,name'],
            'grade_level' => ['required', 'integer', 'in:7,8,9'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
        ]));

        return response()->json(['data' => $class], 201);
    }

    public function academicYears(Request $request)
    {
        if ($request->isMethod('get')) {
            return response()->json(['data' => AcademicYear::orderByDesc('start_year')->get()]);
        }

        $year = AcademicYear::create($request->validate([
            'name' => ['required', 'unique:academic_years,name'],
            'start_year' => ['required', 'integer', 'unique:academic_years,start_year'],
        ]));

        return response()->json(['data' => $year], 201);
    }

    public function activate(AcademicYear $academicYear, AcademicYearService $service)
    {
        $service->activate($academicYear);

        return response()->json(['data' => $academicYear->fresh(), 'message' => 'Tahun ajaran aktif diperbarui.']);
    }
}
