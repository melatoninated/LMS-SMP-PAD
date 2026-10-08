<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'SMP LMS' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
@auth
<div class="flex min-h-screen flex-col md:flex-row">
    <aside class="w-full bg-slate-900 p-4 text-white md:min-h-screen md:w-64">
        <div class="mb-5 text-xl font-bold">SMP LMS</div>
        @php
            $menus = [
              'admin' => [['Dashboard', route('admin.dashboard')], ['Guru', route('admin.teachers.index')], ['Siswa', route('admin.students.index')], ['Orang Tua', route('admin.parents.index')], ['Kelas', route('admin.classes.index')], ['Tahun Ajaran', route('admin.years.index')], ['Import Siswa', route('admin.students.import.form')], ['Ganti Password', route('password.change')]],
              'teacher' => [['Dashboard', route('teacher.dashboard')], ['Mata Pelajaran', route('teacher.courses.index')], ['Nilai', route('teacher.grades.index')], ['Export', route('teacher.grades.export')], ['Ganti Password', route('password.change')]],
              'student' => [['Dashboard', route('student.dashboard')], ['Mata Pelajaran', route('student.courses.index')], ['Profil', route('student.profile')], ['Ganti Password', route('password.change')]],
              'parent' => [['Dashboard', route('parent.dashboard')], ['Ganti Password', route('password.change')]],
            ][auth()->user()->role];
        @endphp
        <nav class="flex flex-wrap gap-1 md:grid">
            @foreach($menus as [$label, $url])
                <a class="rounded-lg px-3 py-2 text-sm text-slate-200 hover:bg-slate-800" href="{{ $url }}">{{ $label }}</a>
            @endforeach
        </nav>
    </aside>
    <main class="flex-1 p-4 md:p-6">
        <header class="mb-5 flex flex-col justify-between gap-3 md:flex-row md:items-center">
            <h1 class="text-2xl font-bold">{{ $title ?? 'SMP LMS' }}</h1>
            <form method="post" action="{{ route('logout') }}">@csrf <span class="mr-3 text-sm text-slate-600">{{ auth()->user()->name }}</span><button class="btn-secondary">Keluar</button></form>
        </header>
        @if(session('status')) <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">{{ session('status') }}</div> @endif
        @if($errors->any()) <div class="mb-4 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div> @endif
        {{ $slot }}
    </main>
</div>
@else
    {{ $slot }}
@endauth
</body>
</html>
