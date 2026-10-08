<x-layouts.app title="Mata Pelajaran">
    <section class="panel overflow-x-auto"><table class="w-full"><tr><th class="table-cell">Nama</th><th class="table-cell">Kelas</th><th class="table-cell">Aksi</th></tr>
        @foreach($courses as $course)<tr><td class="table-cell"><a href="{{ route('student.courses.show', $course) }}">{{ $course->name }}</a></td><td class="table-cell">{{ $course->target_grade }}</td><td class="table-cell">@if($course->target_grade <= ($grade ?? 0))<form method="post" action="{{ route('student.courses.enroll', $course) }}">@csrf<button class="btn">Enroll</button></form>@else Terkunci @endif</td></tr>@endforeach
    </table></section>
</x-layouts.app>
