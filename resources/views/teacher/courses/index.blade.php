<x-layouts.app title="Mata Pelajaran">
    <form method="post" action="{{ route('teacher.courses.store') }}" class="panel mb-4 grid gap-3 md:grid-cols-3">@csrf<input class="input" name="name" placeholder="Nama" required><select class="input" name="target_grade"><option>7</option><option>8</option><option>9</option></select><textarea class="input" name="description" placeholder="Deskripsi"></textarea><button class="btn">Tambah</button></form>
    <section class="panel">@foreach($courses as $course)<p><a class="font-semibold text-blue-700" href="{{ route('teacher.courses.show', $course) }}">{{ $course->name }}</a> - Kelas {{ $course->target_grade }}</p>@endforeach</section>
</x-layouts.app>
