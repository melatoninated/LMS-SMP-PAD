<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function log(Request $request, ExamSession $examSession)
    {
        abort_unless($examSession->student_id === auth()->user()->student?->id, 403);
        $data = $request->validate(['event' => ['required'], 'detail' => ['nullable']]);
        $examSession->logs()->create($data);

        return response()->noContent();
    }
}
