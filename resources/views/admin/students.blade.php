<x-layouts.app title="Siswa">
    <form method="post" action="{{ route('admin.students.store') }}" class="panel mb-4 grid gap-3 md:grid-cols-2">
        @csrf
        <input class="input" name="name" placeholder="Nama" required>
        <input class="input" name="nisn" placeholder="NISN" required>
        <input class="input" type="date" name="birth_date" required>
        <input class="input" type="number" name="cohort_year" placeholder="Angkatan" required>
        <select class="input" name="class_room_id">@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select>
        <button class="btn">Tambah Siswa</button>
    </form>
    <section class="panel overflow-x-auto"><table class="w-full"><tr><th class="table-cell">Nama</th><th class="table-cell">NISN</th><th class="table-cell">Kelas</th><th class="table-cell">Aksi</th></tr>
        @foreach($students as $student)<tr><td class="table-cell">{{ $student->user->name }}</td><td class="table-cell">{{ $student->nisn }}</td><td class="table-cell">{{ $student->classRoom?->name }}</td><td class="table-cell"><form method="post" action="{{ route('admin.students.reset-password', $student) }}">@csrf<button class="btn">Reset Password</button></form></td></tr>@endforeach
    </table></section>
</x-layouts.app>
