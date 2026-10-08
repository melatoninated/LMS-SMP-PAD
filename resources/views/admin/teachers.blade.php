<x-layouts.app title="Guru">
    <form method="post" action="{{ route('admin.teachers.store') }}" class="panel mb-4 grid gap-3 md:grid-cols-3">@csrf<input class="input" name="name" placeholder="Nama" required><input class="input" type="email" name="email" placeholder="Email" required><input class="input" name="employee_no" placeholder="NIP"><button class="btn">Tambah</button></form>
    <section class="panel">@foreach($teachers as $teacher)<p>{{ $teacher->user->name }} - {{ $teacher->user->username }}</p>@endforeach</section>
</x-layouts.app>
