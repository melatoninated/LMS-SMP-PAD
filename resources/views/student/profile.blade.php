<x-layouts.app title="Profil">
    <form method="post" action="{{ route('student.profile.class') }}" class="panel mb-4">@csrf<label class="label">Kelas Aktual<select class="input" name="class_room_id">@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select></label><button class="btn mt-3">Perbarui Kelas</button></form>
    <form method="post" action="{{ route('student.profile.profiling') }}" class="panel mb-4">@csrf@foreach($questions as $question)<label class="label mb-3">{{ $question->prompt }}<input class="input" type="number" min="1" max="5" name="answers[{{ $question->id }}]" value="3"></label>@endforeach<button class="btn">Simpan Profiling</button></form>
    <section class="panel"><h2 class="mb-3 font-bold">Rekomendasi Materi</h2>@forelse($recommendations as $item)<p>{{ $item }}</p>@empty<p>Belum ada rekomendasi khusus.</p>@endforelse</section>
</x-layouts.app>
