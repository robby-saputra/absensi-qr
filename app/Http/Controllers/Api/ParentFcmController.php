<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParentFcmController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required|exists:users,id',
            'token' => 'required|string|max:500',
            'device_name' => 'nullable|string|max:120',
        ]);

        DB::table('parent_fcm_tokens')->updateOrInsert(
            ['token' => $request->token],
            [
                'siswa_id' => $request->siswa_id,
                'device_name' => $request->device_name,
                'last_used_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Token notifikasi orang tua tersimpan.',
        ]);
    }
}
