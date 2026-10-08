<x-layouts.app title="Dashboard Siswa">
    @if($classWarning)<div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-amber-900">Tingkat kelas Anda berubah. Perbarui kelas aktual di Profil.</div>@endif
    <div class="mb-4 grid gap-4 md:grid-cols-2"><div class="panel">Tingkat Sistem <b class="block text-3xl">{{ $expectedGrade ?? 'Lulus' }}</b></div><div class="panel">Course Diikuti <b class="block text-3xl">{{ $courses->count() }}</b></div></div>
    <section class="panel">@foreach($courses as $course)<p>{{ $course->name }}</p>@endforeach</section>
</x-layouts.app>
