<?php

namespace App\Http\Controllers;

class ParentDashboardController extends Controller
{
    public function dashboard()
    {
        return view('parent.dashboard', [
            'students' => auth()->user()->parentProfile->students()->with('user', 'classRoom', 'courses')->get(),
        ]);
    }
}
