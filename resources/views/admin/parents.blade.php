<x-layouts.app title="Orang Tua">
    <form method="post" action="{{ route('admin.parents.store') }}" class="panel mb-4 grid gap-3 md:grid-cols-4">@csrf<input class="input" name="name" placeholder="Nama" required><input class="input" name="username" placeholder="Username" required><input class="input" name="phone" placeholder="Telepon"><select class="input" name="student_id"><option value="">Relasi anak</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->user->name }}</option>@endforeach</select><button class="btn">Tambah</button></form>
    <section class="panel">@foreach($parents as $parent)<p>{{ $parent->user->name }} - {{ $parent->students->pluck('user.name')->join(', ') }}</p>@endforeach</section>
</x-layouts.app>
