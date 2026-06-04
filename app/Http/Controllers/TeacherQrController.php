<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TeacherQrController extends Controller
{
    public function show(string $token)
    {
        $teacher = Teacher::where('qr_token', $token)
            ->where('is_archived', false)
            ->with(['user', 'directions'])
            ->firstOrFail();

        $ratingUrl = route('student.rate.show', $teacher);

        return view('teacher.qr', compact('teacher', 'ratingUrl'));
    }

    public function qrImage(string $token)
    {
        $teacher = Teacher::where('qr_token', $token)
            ->where('is_archived', false)
            ->firstOrFail();

        $url = route('teacher.qr', $token);
        $svg = QrCode::format('svg')->size(300)->generate($url);

        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }
}
