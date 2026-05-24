<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // validasi input
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        // cari user
        $user = User::where('username', $request->username)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        // cek password (sementara masih plain text)
        if (! $this->passwordMatches($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password salah'
            ], 401);
        }

        if (! (bool) ($user->aktif ?? true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun sedang dinonaktifkan'
            ], 403);
        }

        // 🔥 generate token baru setiap login
        $user->load('kelasRelasi');

        $token = Str::random(60);

        // simpan token ke database
        $user->remember_token = $token;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',

            // 🔥 INI YANG PENTING UNTUK API
            'token' => $token,

            'user' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'username' => $user->username,
                'role' => $user->role,
                'kelas_id' => $user->kelas_id,
                'kelas' => $user->kelas
            ]
        ]);
    }

    private function passwordMatches(string $plain, ?string $stored): bool
    {
        if (! $stored) {
            return false;
        }

        $isLaravelHash = str_starts_with($stored, '$2y$')
            || str_starts_with($stored, '$2a$')
            || str_starts_with($stored, '$argon2');

        return ($isLaravelHash && Hash::check($plain, $stored))
            || hash_equals($stored, $plain)
            || (strlen($stored) === 32 && hash_equals($stored, md5($plain)));
    }
}
