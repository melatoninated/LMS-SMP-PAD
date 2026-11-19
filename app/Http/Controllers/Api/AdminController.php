<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\StudentsImport;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Course;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\AcademicYearService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    public function dashboard()
    {
        return response()->json(['data' => [
            'students' => Student::where('is_deleted', false)->count(),
            'teachers' => Teacher::where('is_deleted', false)->count(),
            'parents' => ParentProfile::where('is_deleted', false)->count(),
            'courses' => Course::where('is_deleted', false)->count(),
            'active_year' => AcademicYear::where('is_active', true)->where('is_deleted', false)->first(),
            'classes' => ClassRoom::where('is_deleted', false)->withCount('students')->get(),
            'alerts' => [
                'students_need_class_update' => app(AcademicYearService::class)->studentsNeedingClassUpdate()->count(),
            ],
        ]]);
    }

    public function teachers(Request $request)
    {
        if ($request->isMethod('get')) {
            return response()->json(['data' => Teacher::where('is_deleted', false)->with('user')->get()]);
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
            return response()->json(['data' => Student::where('is_deleted', false)->with(['user', 'classRoom'])->get()]);
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
            'password' => Carbon::parse($data['birth_date'])->format('Ymd'),
            'role' => 'student',
            'must_change_password' => true,
        ]);
        unset($data['name']);

        return response()->json(['data' => Student::create($data + ['user_id' => $user->id])->load(['user', 'classRoom'])], 201);
    }

    public function resetStudentPassword(Student $student)
    {
        $student->user->update(['password' => $student->birth_date->format('Ymd'), 'must_change_password' => true]);

        return response()->json(['message' => 'Password siswa direset ke tanggal lahir (YYYYMMDD).']);
    }

    public function importStudents(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx', 'max:10240']]);
        $import = new StudentsImport;
        Excel::import($import, $request->file('file'));
        if ($import->failures) {
            return response()->json([
                'message' => 'Impor dibatalkan; tidak ada data yang disimpan.',
                'failures' => $import->failures,
            ], 422);
        }
        return response()->json(['message' => 'Impor berhasil.', 'imported' => $import->imported], 201);
    }

    public function parents(Request $request)
    {
        if ($request->isMethod('get')) {
            return response()->json(['data' => ParentProfile::where('is_deleted', false)->with('user', 'student.user')->get()]);
        }

        $data = $request->validate([
            'name' => ['required'],
            'phone' => ['nullable'],
            'address' => ['nullable'],
            'student_id' => ['required', 'exists:students,id', 'unique:parents,student_id'],
        ]);
        $user = User::create([
            'name' => $data['name'],
            'username' => Student::findOrFail($data['student_id'])->nisn,
            'password' => Student::findOrFail($data['student_id'])->birth_date->format('Ymd'),
            'role' => 'parent',
            'must_change_password' => true,
        ]);
        $parent = ParentProfile::create([
            'user_id' => $user->id,
            'student_id' => $data['student_id'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
        ]);
        $parent->students()->sync([$data['student_id']]);

        return response()->json(['data' => $parent->load('user', 'students.user')], 201);
    }

    public function classes(Request $request)
    {
        if ($request->isMethod('get')) {
            return response()->json(['data' => ClassRoom::where('is_deleted', false)->withCount('students')->orderBy('grade_level')->orderBy('name')->get()]);
        }

        $class = ClassRoom::create($request->validate([
            'name' => ['required', 'unique:class_rooms,name'],
            'grade_level' => ['required', 'integer', 'in:7,8,9'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
        ]));

        return response()->json(['data' => $class], 201);
    }

    public function academicYears(Request $request)
    {
        if ($request->isMethod('get')) {
            return response()->json(['data' => AcademicYear::where('is_deleted', false)->orderByDesc('start_year')->get()]);
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

    public function teacher(Request $request, Teacher $teacher)
    {
        if ($request->isMethod('delete')) {
            return $this->softDelete($teacher, $teacher->user);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($teacher->user_id)],
            'employee_no' => ['required', 'string', Rule::unique('teachers', 'employee_no')->ignore($teacher->id), Rule::unique('users', 'username')->ignore($teacher->user_id)],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);
        DB::transaction(function () use ($teacher, $data) {
            $teacher->user->update(['name' => $data['name'], 'email' => $data['email'], 'username' => $data['employee_no']]);
            $teacher->update(['employee_no' => $data['employee_no'], 'phone' => $data['phone'] ?? null]);
        });
        return response()->json(['data' => $teacher->fresh('user')]);
    }

    public function student(Request $request, Student $student)
    {
        if ($request->isMethod('delete')) {
            return $this->softDelete($student, $student->user);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nisn' => ['required', 'digits:10', Rule::unique('students', 'nisn')->ignore($student->id)],
            'birth_date' => ['required', 'date_format:Y-m-d'],
            'cohort_year' => ['required', 'integer'],
            'class_room_id' => ['nullable', 'exists:class_rooms,id'],
        ]);
        DB::transaction(function () use ($student, $data) {
            $student->user->update(['name' => $data['name'], 'username' => $data['nisn']]);
            $student->update(collect($data)->except('name')->all());
        });
        return response()->json(['data' => $student->fresh(['user', 'classRoom'])]);
    }

    public function parent(Request $request, ParentProfile $parent)
    {
        if ($request->isMethod('delete')) {
            return $this->softDelete($parent, $parent->user);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'student_id' => ['required', 'exists:students,id', Rule::unique('parents', 'student_id')->ignore($parent->id)],
            'phone' => ['nullable', 'string', 'max:50'], 'address' => ['nullable', 'string'],
        ]);
        $student = Student::findOrFail($data['student_id']);
        DB::transaction(function () use ($parent, $student, $data) {
            $parent->user->update(['name' => $data['name'], 'username' => $student->nisn]);
            $parent->update(['student_id' => $student->id, 'phone' => $data['phone'] ?? null, 'address' => $data['address'] ?? null]);
            $parent->students()->sync([$student->id]);
        });
        return response()->json(['data' => $parent->fresh(['user', 'student.user'])]);
    }

    public function classRoom(Request $request, ClassRoom $classRoom)
    {
        if ($request->isMethod('delete')) return $this->softDelete($classRoom);
        $classRoom->update($request->validate([
            'name' => ['required', Rule::unique('class_rooms', 'name')->ignore($classRoom->id)],
            'grade_level' => ['required', 'integer', 'in:7,8,9'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]));
        return response()->json(['data' => $classRoom->fresh()]);
    }

    public function academicYear(Request $request, AcademicYear $academicYear)
    {
        if ($request->isMethod('delete')) return $this->softDelete($academicYear);
        $academicYear->update($request->validate([
            'name' => ['required', 'regex:/^\d{4}\/\d{4}$/', Rule::unique('academic_years', 'name')->ignore($academicYear->id)],
            'start_year' => ['required', 'integer', Rule::unique('academic_years', 'start_year')->ignore($academicYear->id)],
        ]));
        return response()->json(['data' => $academicYear->fresh()]);
    }

    public function subjects(Request $request)
    {
        if ($request->isMethod('get')) return response()->json(['data' => Subject::where('is_deleted', false)->get()]);
        $subject = Subject::create($request->validate(['code' => ['required', 'string', 'max:50', 'unique:subjects,code'], 'name' => ['required', 'string', 'max:255', 'unique:subjects,name']]));
        return response()->json(['data' => $subject], 201);
    }

    public function subject(Request $request, Subject $subject)
    {
        if ($request->isMethod('delete')) return $this->softDelete($subject);
        $subject->update($request->validate(['code' => ['required', Rule::unique('subjects', 'code')->ignore($subject->id)], 'name' => ['required', Rule::unique('subjects', 'name')->ignore($subject->id)]]));
        return response()->json(['data' => $subject->fresh()]);
    }

    public function teachingAssignments(Request $request)
    {
        if ($request->isMethod('get')) return response()->json(['data' => TeachingAssignment::where('is_deleted', false)->with('teacher.user', 'subject', 'academicYear', 'classRoom')->get()]);
        $data = $this->validatedAssignment($request);
        $assignment = TeachingAssignment::create($data);
        return response()->json(['data' => $assignment->load('teacher.user', 'subject', 'academicYear', 'classRoom')], 201);
    }

    public function teachingAssignment(Request $request, TeachingAssignment $teachingAssignment)
    {
        if ($request->isMethod('delete')) return $this->softDelete($teachingAssignment);
        $teachingAssignment->update($this->validatedAssignment($request));
        return response()->json(['data' => $teachingAssignment->fresh('teacher.user', 'subject', 'academicYear', 'classRoom')]);
    }

    private function validatedAssignment(Request $request): array
    {
        $data = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'], 'subject_id' => ['required', 'exists:subjects,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'], 'class_room_id' => ['required', 'exists:class_rooms,id'],
            'grade_level' => ['required', 'integer', 'in:7,8,9'],
        ]);
        $class = ClassRoom::findOrFail($data['class_room_id']);
        abort_unless($class->grade_level === (int) $data['grade_level'] && $class->academic_year_id === (int) $data['academic_year_id'], 422, 'Rombel harus sesuai tingkat dan tahun ajaran penugasan.');
        return $data;
    }

    private function softDelete(object $model, ?User $user = null)
    {
        $attributes = ['is_deleted' => true];
        if (isset($model->is_active)) $attributes['is_active'] = false;
        $model->update($attributes);
        $user?->update(['is_deleted' => true, 'is_active' => false]);
        return response()->noContent();
    }
}
