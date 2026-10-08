<x-layouts.app title="Dashboard Orang Tua">
    <section class="panel overflow-x-auto"><table class="w-full"><tr><th class="table-cell">Anak</th><th class="table-cell">Kelas</th><th class="table-cell">Mata Pelajaran</th></tr>
        @foreach($students as $student)<tr><td class="table-cell">{{ $student->user->name }}</td><td class="table-cell">{{ $student->classRoom?->name }}</td><td class="table-cell">{{ $student->courses->pluck('name')->join(', ') ?: 'Belum ada course' }}</td></tr>@endforeach
    </table></section>
</x-layouts.app>
