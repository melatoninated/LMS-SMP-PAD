<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Material;
use App\Models\Section;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        return view('teacher.courses.index', ['courses' => auth()->user()->teacher->courses()->withCount('students')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required'], 'description' => ['nullable'], 'target_grade' => ['required', 'integer', 'in:7,8,9']]);
        auth()->user()->teacher->courses()->create($data);

        return back()->with('status', 'Mata pelajaran berhasil dibuat.');
    }

    public function show(Course $course)
    {
        $this->authorize('manage', $course);

        return view('teacher.courses.show', ['course' => $course->load('sections.materials', 'sections.assessments')]);
    }

    public function update(Request $request, Course $course)
    {
        $this->authorize('manage', $course);
        $course->update($request->validate(['name' => ['required'], 'description' => ['nullable'], 'target_grade' => ['required', 'integer', 'in:7,8,9']]));

        return back()->with('status', 'Mata pelajaran diperbarui.');
    }

    public function destroy(Course $course)
    {
        $this->authorize('manage', $course);
        $course->delete();

        return redirect()->route('teacher.courses.index')->with('status', 'Mata pelajaran dihapus.');
    }

    public function storeSection(Request $request, Course $course)
    {
        $this->authorize('manage', $course);
        $data = $request->validate(['title' => ['required']]);
        $course->sections()->create([...$data, 'position' => $course->sections()->count() + 1]);

        return back()->with('status', 'Pertemuan ditambahkan.');
    }

    public function storeMaterial(Request $request, Section $section)
    {
        $this->authorize('manage', $section->course);
        $data = $request->validate([
            'title' => ['required'], 'type' => ['required', 'in:text,pdf,video,audio,file'],
            'body' => ['nullable'], 'file_path' => ['nullable'], 'target_grade' => ['required', 'integer', 'in:7,8,9'],
        ]);
        Material::create([...$data, 'section_id' => $section->id]);

        return back()->with('status', 'Materi ditambahkan.');
    }
}
