<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Controller;
use App\Models\QrCode;
use Illuminate\Http\Request;

class ManualAbsensiController extends Controller
{
    public function store(Request $request)
    {
        $user = session('user');

        if (! $user) {
            return back()->with('error', 'User tidak login');
        }

        $qr = QrCode::where('token', $request->token)->first();

        if (! $qr) {
            return back()->with('error', 'QR tidak valid');
        }

        $request->attributes->set('user_login', $user);

        return app(AbsensiController::class)->scan($request);
    }
}
