<x-layouts.app title="{{ $assessment->title }}">
    @unless($canAttempt)
        <section class="panel">Assessment kelas sebelumnya hanya dapat dibaca, tidak dapat dikerjakan ulang.</section>
    @else
        @if($examSession)
            <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-amber-900">
                Sisa waktu: <b id="timer">{{ $remainingSeconds }}</b> detik
                <button class="btn ml-3" type="button" onclick="document.documentElement.requestFullscreen?.()">Fullscreen</button>
            </div>
        @endif
        <form id="assessment-form" method="post" action="{{ route('student.assessments.submit', $assessment) }}" class="panel">
            @csrf
            @foreach($assessment->questions as $question)
                <div class="mb-4 rounded-lg border border-slate-200 p-4">
                    <p class="mb-3 font-semibold">{{ $question->prompt }}</p>
                    @if($question->type === 'multiple_choice')
                        @foreach($question->options as $option)<label class="mb-2 block"><input name="answers[{{ $question->id }}]" type="radio" value="{{ $option->id }}" required> {{ $option->option_text }}</label>@endforeach
                    @elseif($question->type === 'true_false')
                        <label class="mr-4"><input name="answers[{{ $question->id }}]" type="radio" value="true" required> Benar</label>
                        <label><input name="answers[{{ $question->id }}]" type="radio" value="false"> Salah</label>
                    @elseif($question->type === 'file_upload')
                        <input class="input" name="answers[{{ $question->id }}]" placeholder="URL file jawaban" required>
                    @else
                        <textarea class="input" name="answers[{{ $question->id }}]" required></textarea>
                    @endif
                </div>
            @endforeach
            <button class="btn">Submit</button>
        </form>
        @if($examSession)
            <script>
                let remain = Number(@json($remainingSeconds));
                const sessionUrl = @json(route('student.exams.log', $examSession));
                function logExam(event) {
                    fetch(sessionUrl, { method: 'POST', headers: {'X-CSRF-TOKEN': @json(csrf_token()), 'Content-Type': 'application/json'}, body: JSON.stringify({event}) });
                }
                document.addEventListener('copy', event => { event.preventDefault(); alert('Fitur copy tidak tersedia pada halaman ini.'); logExam('copy_blocked'); });
                document.addEventListener('paste', event => event.preventDefault());
                document.addEventListener('selectstart', event => event.preventDefault());
                document.addEventListener('fullscreenchange', () => { if (!document.fullscreenElement) logExam('fullscreen_exit'); });
                setInterval(() => {
                    remain = Math.max(0, remain - 1);
                    document.getElementById('timer').textContent = remain;
                    if (remain === 0) document.getElementById('assessment-form').submit();
                }, 1000);
            </script>
        @endif
    @endunless
</x-layouts.app>
