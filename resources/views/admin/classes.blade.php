<x-layouts.app title="Kelas">
    <form method="post" action="{{ route('admin.classes.store') }}" class="panel mb-4 flex flex-wrap gap-3">@csrf<input class="input max-w-48" name="name" placeholder="7A" required><select class="input max-w-32" name="grade_level"><option>7</option><option>8</option><option>9</option></select><button class="btn">Tambah</button></form>
    <section class="panel">@foreach($classes as $class)<p>{{ $class->name }} - Tingkat {{ $class->grade_level }}</p>@endforeach</section>
</x-layouts.app>
