<x-layouts.app title="Import Siswa">
    <section class="panel mb-4"><a class="btn-secondary" href="{{ route('admin.students.import.template') }}">Download Template Excel</a></section>
    <form method="post" action="{{ route('admin.students.import') }}" enctype="multipart/form-data" class="panel">@csrf<input class="input mb-4" type="file" name="file" accept=".xlsx" required><button class="btn">Import</button></form>
    @if(session('import_failures'))<pre class="panel mt-4 text-sm">{{ json_encode(session('import_failures'), JSON_PRETTY_PRINT) }}</pre>@endif
</x-layouts.app>
