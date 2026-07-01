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
        // Bagian ini memastikan username dan password wajib dikirim dari aplikasi mobile.
        // Jika salah satu kosong, Laravel langsung mengembalikan error validasi.
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // Sistem pertama kali mencari akun berdasarkan username biasa.
        // Cara ini dipakai untuk login siswa atau user lain yang punya username di tabel users.
        $user = User::where('username', $request->username)->first();

        if (! $user) {
            // Jika username biasa tidak ditemukan, sistem mencoba pola login orang tua.
            // Orang tua tidak punya role sendiri di tabel users, tetapi masuk lewat data siswa.
            $siswaOrtu = User::where('role', 'siswa')
                ->where(function ($query) use ($request) {
                    $query->where('no_ortu', $request->username)
                        ->orWhere('nis', $request->username);
                })
                ->first();

            $loginPakaiNoOrtu = $siswaOrtu && $siswaOrtu->no_ortu === $request->username;
            $loginPakaiNis = $siswaOrtu && $siswaOrtu->nis === $request->username;

            // Login orang tua dibuat fleksibel:
            // - Jika username memakai nomor orang tua, passwordnya dicocokkan dengan NIS/username siswa.
            // - Jika username memakai NIS siswa, passwordnya dicocokkan dengan nomor orang tua.
            $passwordCocok = $siswaOrtu && (
                ($loginPakaiNoOrtu && $this->passwordMatches($request->password, (string) ($siswaOrtu->nis ?: $siswaOrtu->username)))
                || ($loginPakaiNis && $this->passwordMatches($request->password, (string) $siswaOrtu->no_ortu))
            );

            if ($passwordCocok) {
                // Token ini menjadi kunci akses API untuk orang tua.
                // Role context disimpan sebagai orang_tua supaya middleware tahu aksesnya milik orang tua.
                $token = Str::random(60);
                $this->simpanTokenApi($siswaOrtu, $token, 'orang_tua', $request);
                $this->catatStatusLogin($siswaOrtu, $request, 'orang_tua');

                // Response ini dikirim ke aplikasi mobile agar aplikasi tahu data anak yang dipantau.
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

        // Bagian ini mengecek password akun yang ditemukan.
        // Fungsi passwordMatches mendukung password hash Laravel, plain text lama, dan MD5 lama.
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

        // Relasi kelas dimuat agar response login bisa mengirim informasi kelas siswa.
        $user->load('kelasRelasi');

        // Setiap login berhasil akan dibuat token baru.
        // Token ini dipakai aplikasi mobile pada request berikutnya.
        $token = Str::random(60);

        // remember_token dipakai untuk kompatibilitas lama, sedangkan api_access_tokens
        // menyimpan hash token yang lebih aman untuk middleware API.
        $user->remember_token = $token;
        $user->save();
        $this->simpanTokenApi($user, $token, $user->role, $request);
        $this->catatStatusLogin($user, $request, $user->role);

        // Response sukses berisi token dan data dasar user yang dibutuhkan aplikasi.
        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',

            // Token ini harus disimpan aplikasi mobile dan dikirim lagi saat scan atau membuka dashboard.
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
        // Jika password di database kosong, login harus ditolak.
        if (! $stored) {
            return false;
        }

        // Sistem mendeteksi apakah password sudah memakai format hash Laravel.
        $isLaravelHash = str_starts_with($stored, '$2y$')
            || str_starts_with($stored, '$2a$')
            || str_starts_with($stored, '$argon2');

        // Tiga model password didukung agar data lama tetap bisa login:
        // hash Laravel, plain text lama, dan MD5 lama.
        return ($isLaravelHash && Hash::check($plain, $stored))
            || hash_equals($stored, $plain)
            || (strlen($stored) === 32 && hash_equals($stored, md5($plain)));
    }

    private function catatStatusLogin(User $user, Request $request, ?string $role = null): void
    {
        // Jika tabel status login belum ada, fungsi dihentikan agar aplikasi tidak error.
        if (! Schema::hasTable('user_login_statuses')) {
            return;
        }

        // Data ini dipakai admin untuk melihat siapa saja yang sedang online.
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

    private function simpanTokenApi(User $user, string $plainToken, string $roleContext, Request $request): void
    {
        // Jika tabel token API belum ada, login tetap berjalan untuk kompatibilitas instalasi lama.
        if (! Schema::hasTable('api_access_tokens')) {
            return;
        }

        // Token asli tidak disimpan langsung, tetapi diubah menjadi hash SHA-256.
        // Tujuannya agar token lebih aman jika database dilihat orang lain.
        DB::table('api_access_tokens')->insert([
            'user_id' => $user->id,
            'role_context' => $roleContext,
            'token_hash' => hash('sha256', $plainToken),
            'device_name' => substr((string) ($request->header('X-Device-Name') ?: $request->userAgent()), 0, 255),
            'ip_address' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
