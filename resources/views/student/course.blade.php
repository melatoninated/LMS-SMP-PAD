<x-layouts.app title="{{ $course->name }}">
    @foreach($course->sections as $section)
        <section class="panel mb-4">
            <h2 class="mb-3 font-bold">{{ $section->title }}</h2>
            <h3 class="font-semibold text-slate-600">Materi</h3>
            @forelse($section->materials->where('target_grade', '<=', $grade) as $material)
                <div class="mb-2 rounded-lg border border-slate-200 p-3">
                    <p class="font-semibold">{{ $material->title }}</p>
                    <p class="text-sm text-slate-600">{{ $material->body }}</p>
                    @if($material->file_path)<a class="text-sm text-blue-700" href="{{ $material->file_path }}" target="_blank">Buka file/media</a>@endif
                    <form method="post" action="{{ route('student.materials.complete', $material) }}" class="mt-2">@csrf<button class="btn">Tandai selesai</button></form>
                </div>
            @empty <p>Belum ada materi pada pertemuan ini.</p> @endforelse
            <h3 class="mt-4 font-semibold text-slate-600">Assessment</h3>
            @forelse($section->assessments->where('target_grade', '<=', $grade) as $assessment)
                <p><a class="text-blue-700" href="{{ route('student.assessments.show', $assessment) }}">{{ $assessment->title }}</a> {{ $assessment->is_exam ? '(Ujian)' : '' }}</p>
            @empty <p>Belum ada assessment.</p> @endforelse
        </section>
    @endforeach
</x-layouts.app>
