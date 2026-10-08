<x-layouts.app title="Dashboard Guru">
    <div class="mb-4 grid gap-4 md:grid-cols-2"><div class="panel">Course <b class="block text-3xl">{{ $courses->count() }}</b></div><div class="panel">Perlu Dinilai <b class="block text-3xl">{{ $needGrading }}</b></div></div>
    <section class="panel">@foreach($courses as $course)<p>{{ $course->name }} - {{ $course->students_count }} siswa</p>@endforeach</section>
</x-layouts.app>
