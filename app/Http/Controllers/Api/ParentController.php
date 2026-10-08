<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ParentController extends Controller
{
    public function dashboard(Request $request)
    {
        return response()->json(['data' => $request->user()->parentProfile->students()->with('user', 'classRoom', 'courses')->get()]);
    }
}
