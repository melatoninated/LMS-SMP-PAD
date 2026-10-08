<x-layouts.app title="Dashboard Administrator">
    <div class="mb-4 grid gap-4 md:grid-cols-4">
        <div class="panel">Siswa <b class="block text-3xl">{{ $students }}</b></div>
        <div class="panel">Guru <b class="block text-3xl">{{ $teachers }}</b></div>
        <div class="panel">Orang Tua <b class="block text-3xl">{{ $parents }}</b></div>
        <div class="panel">Course <b class="block text-3xl">{{ $courses }}</b></div>
    </div>
    <div class="grid gap-4 md:grid-cols-2">
        <section class="panel"><h2 class="mb-3 font-bold">Tahun Ajaran Aktif</h2><p>{{ $activeYear?->name ?? '-' }}</p></section>
        <section class="panel"><h2 class="mb-3 font-bold">Aktivitas Terbaru</h2>@foreach($logs as $log)<p class="text-sm">{{ $log->created_at }} - {{ $log->event }}</p>@endforeach</section>
    </div>
    <section class="panel mt-4"><h2 class="mb-3 font-bold">Distribusi Siswa</h2>@foreach($classes as $class)<p>{{ $class->name }}: {{ $class->students_count }}</p>@endforeach</section>
</x-layouts.app>
