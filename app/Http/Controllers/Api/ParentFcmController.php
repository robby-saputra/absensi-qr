<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParentFcmController extends Controller
{
    public function register(Request $request)
    {
        $authenticatedUser = $request->attributes->get('user_login');

        $request->validate([
            'siswa_id' => 'required|exists:users,id',
            'token' => 'required|string|max:500',
            'device_name' => 'nullable|string|max:120',
            'audience' => 'required|in:siswa,orang_tua',
        ]);

        if (! $authenticatedUser || (int) $authenticatedUser->id !== (int) $request->siswa_id) {
            return response()->json(['status' => 'error', 'message' => 'Akses token notifikasi ditolak.'], 403);
        }

        $roleContext = (string) $request->attributes->get('api_role', '');
        if ($roleContext !== $request->audience) {
            return response()->json(['status' => 'error', 'message' => 'Jenis akun notifikasi tidak sesuai.'], 403);
        }

        DB::table('parent_fcm_tokens')->updateOrInsert(
            ['token' => $request->token],
            [
                'siswa_id' => $request->siswa_id,
                'audience' => $request->audience,
                'device_name' => $request->device_name,
                'last_used_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Token notifikasi perangkat tersimpan.',
        ]);
    }
}
