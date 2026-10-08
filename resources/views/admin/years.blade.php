<x-layouts.app title="Tahun Ajaran">
    <form method="post" action="{{ route('admin.years.store') }}" class="panel mb-4 flex flex-wrap gap-3">@csrf<input class="input max-w-48" name="name" placeholder="2029/2030" required><input class="input max-w-32" type="number" name="start_year" required><button class="btn">Tambah</button></form>
    <section class="panel">@foreach($years as $year)<div class="mb-2 flex items-center justify-between"><span>{{ $year->name }} {{ $year->is_active ? '(Aktif)' : '' }}</span>@unless($year->is_active)<form method="post" action="{{ route('admin.years.activate', $year) }}">@csrf<button class="btn">Aktifkan</button></form>@endunless</div>@endforeach</section>
</x-layouts.app>
