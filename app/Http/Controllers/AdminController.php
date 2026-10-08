<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AcademicYearService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard', [
            'students' => Student::count(),
            'teachers' => Teacher::count(),
            'parents' => ParentProfile::count(),
            'courses' => \App\Models\Course::count(),
            'activeYear' => AcademicYear::where('is_active', true)->first(),
            'classes' => ClassRoom::withCount('students')->get(),
            'logs' => \App\Models\AuditLog::latest('id')->limit(8)->get(),
        ]);
    }

    public function index()
    {
        return view('admin.teachers', ['teachers' => Teacher::with('user')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required'], 'email' => ['required', 'email', 'unique:users,email'], 'employee_no' => ['nullable', 'unique:teachers,employee_no']]);
        $user = User::create(['name' => $data['name'], 'username' => $data['email'], 'email' => $data['email'], 'password' => Hash::make('GuruDev!2026'), 'role' => 'teacher']);
        Teacher::create(['user_id' => $user->id, 'employee_no' => $data['employee_no'] ?? null]);

        return back()->with('status', 'Guru berhasil ditambahkan.');
    }

    public function students()
    {
        return view('admin.students', ['students' => Student::with(['user', 'classRoom'])->get(), 'classes' => ClassRoom::orderBy('name')->get()]);
    }

    public function storeStudent(Request $request)
    {
        $data = $request->validate([
            'name' => ['required'], 'nisn' => ['required', 'unique:students,nisn'],
            'birth_date' => ['required', 'date'], 'cohort_year' => ['required', 'integer'],
            'class_room_id' => ['nullable', 'exists:class_rooms,id'],
        ]);
        $user = User::create(['name' => $data['name'], 'username' => $data['nisn'], 'password' => Hash::make($data['birth_date']), 'role' => 'student', 'must_change_password' => true]);
        Student::create([...$data, 'user_id' => $user->id]);

        return back()->with('status', 'Siswa berhasil ditambahkan.');
    }

    public function resetStudentPassword(Student $student)
    {
        $student->user->update(['password' => Hash::make($student->birth_date->toDateString()), 'must_change_password' => true]);
        \App\Models\AuditLog::create(['user_id' => auth()->id(), 'event' => 'student_password_reset', 'entity_type' => Student::class, 'entity_id' => $student->id, 'created_at' => now()]);

        return back()->with('status', 'Password siswa direset ke tanggal lahir.');
    }

    public function parents()
    {
        return view('admin.parents', ['parents' => ParentProfile::with('user', 'students.user')->get(), 'students' => Student::with('user')->get()]);
    }

    public function storeParent(Request $request)
    {
        $data = $request->validate(['name' => ['required'], 'username' => ['required', 'unique:users,username'], 'phone' => ['nullable'], 'student_id' => ['nullable', 'exists:students,id']]);
        $user = User::create(['name' => $data['name'], 'username' => $data['username'], 'password' => Hash::make('OrtuDev!2026'), 'role' => 'parent']);
        $parent = ParentProfile::create(['user_id' => $user->id, 'phone' => $data['phone'] ?? null]);
        if (! empty($data['student_id'])) {
            $parent->students()->attach($data['student_id']);
        }

        return back()->with('status', 'Orang tua berhasil ditambahkan.');
    }

    public function classes()
    {
        return view('admin.classes', ['classes' => ClassRoom::orderBy('grade_level')->orderBy('name')->get()]);
    }

    public function storeClass(Request $request)
    {
        ClassRoom::create($request->validate(['name' => ['required', 'unique:class_rooms,name'], 'grade_level' => ['required', 'integer', 'in:7,8,9']]));

        return back()->with('status', 'Kelas berhasil ditambahkan.');
    }

    public function academicYears()
    {
        return view('admin.years', ['years' => AcademicYear::orderBy('start_year')->get()]);
    }

    public function storeAcademicYear(Request $request)
    {
        AcademicYear::create($request->validate(['name' => ['required', 'unique:academic_years,name'], 'start_year' => ['required', 'integer', 'unique:academic_years,start_year']]));

        return back()->with('status', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function activateAcademicYear(AcademicYear $academicYear, AcademicYearService $service)
    {
        $service->activate($academicYear);

        return back()->with('status', 'Tahun ajaran aktif diperbarui.');
    }

    public function studentImportForm()
    {
        return view('admin.import-students');
    }

    public function studentImportTemplate()
    {
        return Excel::download(new \App\Exports\StudentImportTemplateExport, 'template_import_siswa.xlsx');
    }

    public function importStudents(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx']]);
        $import = new \App\Imports\StudentsImport;
        Excel::import($import, $request->file('file'));

        return back()->with('status', "Import selesai. Berhasil: {$import->imported}. Gagal: ".count($import->failures))->with('import_failures', $import->failures);
    }
}
