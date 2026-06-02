<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // validasi input
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('username', $request->username)->first();

        if (! $user) {
            $siswaOrtu = User::where('role', 'siswa')
                ->where(function ($query) use ($request) {
                    $query->where('no_ortu', $request->username)
                        ->orWhere('nis', $request->username);
                })
                ->first();

            $loginPakaiNoOrtu = $siswaOrtu && $siswaOrtu->no_ortu === $request->username;
            $loginPakaiNis = $siswaOrtu && $siswaOrtu->nis === $request->username;

            $passwordCocok = $siswaOrtu && (
                ($loginPakaiNoOrtu && $this->passwordMatches($request->password, (string) ($siswaOrtu->nis ?: $siswaOrtu->username)))
                || ($loginPakaiNis && $this->passwordMatches($request->password, (string) $siswaOrtu->no_ortu))
            );

            if ($passwordCocok) {
                $token = Str::random(60);
                $this->catatStatusLogin($siswaOrtu, $request, 'orang_tua');

                return response()->json([
                    'status' => 'success',
                    'message' => 'Login orang tua berhasil',
                    'token' => $token,
                    'user' => [
                        'id' => $siswaOrtu->id,
                        'nama' => $siswaOrtu->nama_ortu ?: 'Orang Tua '.$siswaOrtu->nama,
                        'username' => $request->username,
                        'role' => 'orang_tua',
                        'siswa_id' => $siswaOrtu->id,
                        'siswa_nama' => $siswaOrtu->nama,
                        'kelas_id' => $siswaOrtu->kelas_id,
                        'kelas' => optional($siswaOrtu->kelasRelasi)->nama_kelas,
                    ],
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        // cek password (sementara masih plain text)
        if (! $this->passwordMatches($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password salah',
            ], 401);
        }

        if (! (bool) ($user->aktif ?? true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun sedang dinonaktifkan',
            ], 403);
        }

        // 🔥 generate token baru setiap login
        $user->load('kelasRelasi');

        $token = Str::random(60);

        // simpan token ke database
        $user->remember_token = $token;
        $user->save();
        $this->catatStatusLogin($user, $request, $user->role);

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
                'kelas' => $user->kelas,
            ],
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

    private function catatStatusLogin(User $user, Request $request, ?string $role = null): void
    {
        if (! Schema::hasTable('user_login_statuses')) {
            return;
        }

        DB::table('user_login_statuses')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'role' => $role ?: $user->role,
                'is_online' => true,
                'login_at' => now(),
                'last_seen_at' => now(),
                'logout_at' => null,
                'ip_address' => $request->ip(),
                'user_agent' => substr('Android/API '.$request->userAgent(), 0, 255),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
