<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentAuthController extends Controller
{
    public function showLoginForm()
    {
        if (session()->has('student_id')) {
            return redirect()->route('student.dashboard');
        }

        return view('student.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'student_id' => ['required', 'string'],
        ], [
            'student_id.required' => 'ID-kod kiritish majburiy.',
        ]);

        $student = Student::where('student_id', $request->student_id)
            ->where('is_active', true)
            ->first();

        if (!$student) {
            return back()->withErrors([
                'student_id' => "ID-kod topilmadi yoki faol emas: {$request->student_id}",
            ])->withInput();
        }

        session([
            'student_id'   => $student->id,
            'student_name' => $student->full_name,
        ]);

        return redirect()->route('student.dashboard');
    }

    public function logout()
    {
        session()->forget(['student_id', 'student_name']);

        return redirect()->route('student.login')
            ->with('success', 'Tizimdan chiqdingiz.');
    }
}
