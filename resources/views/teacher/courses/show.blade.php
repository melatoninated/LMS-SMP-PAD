<x-layouts.app title="{{ $course->name }}">
    <form method="post" action="{{ route('teacher.sections.store', $course) }}" class="panel mb-4 flex gap-3">@csrf<input class="input" name="title" placeholder="Pertemuan" required><button class="btn">Tambah Pertemuan</button></form>
    @foreach($course->sections as $section)
        <section class="panel mb-4"><h2 class="mb-3 font-bold">{{ $section->title }}</h2>
            <form method="post" action="{{ route('teacher.materials.store', $section) }}" class="mb-3 grid gap-2 md:grid-cols-3">@csrf<input class="input" name="title" placeholder="Judul materi"><select class="input" name="type"><option>text</option><option>pdf</option><option>video</option><option>audio</option><option>file</option></select><input class="input" name="target_grade" value="{{ $course->target_grade }}"><textarea class="input" name="body" placeholder="Konten"></textarea><input class="input" name="file_path" placeholder="URL/file path"><button class="btn">Tambah Materi</button></form>
            <form method="post" action="{{ route('teacher.assessments.store', $section) }}" class="grid gap-2 md:grid-cols-3">@csrf<input class="input" name="title" placeholder="Assessment"><input class="input" name="target_grade" value="{{ $course->target_grade }}"><label class="label"><span><input type="checkbox" name="is_exam" value="1"> Ujian</span></label><input class="input" type="number" name="duration_minutes" placeholder="Durasi"><button class="btn">Tambah Assessment</button></form>
        </section>
    @endforeach
</x-layouts.app>
