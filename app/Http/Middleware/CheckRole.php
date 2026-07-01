<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // API mobile wajib mengirim header Authorization: Bearer <token>.
        // Token ini didapat setelah siswa atau orang tua berhasil login.
        $header = $request->header('Authorization');

        if (! $header) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token tidak ditemukan',
            ], 401);
        }

        // Kata "Bearer " dibuang agar yang tersisa hanya isi tokennya saja.
        $token = str_replace('Bearer ', '', $header);

        // Sistem pertama mencari token pada tabel api_access_tokens.
        // Token yang disimpan di database berbentuk hash, jadi token dari request juga di-hash dulu.
        $apiToken = null;
        if (Schema::hasTable('api_access_tokens')) {
            $apiToken = DB::table('api_access_tokens')->where('token_hash', hash('sha256', $token))
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->first();
        }

        // Jika token baru ditemukan, user diambil dari api_access_tokens.
        // Jika tidak, sistem masih mendukung remember_token lama untuk kompatibilitas.
        $user = $apiToken
            ? User::find($apiToken->user_id)
            : User::where('remember_token', $token)->first();

        // Jika token tidak cocok dengan user manapun, request ditolak.
        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token tidak valid',
            ], 401);
        }

        // Akun yang dinonaktifkan admin tidak boleh memakai API.
        if (! (bool) ($user->aktif ?? true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun sedang dinonaktifkan',
            ], 403);
        }

        // effectiveRole adalah role yang benar-benar dipakai untuk akses API.
        // Contoh: orang tua tetap memakai data user siswa, tetapi role context-nya orang_tua.
        $effectiveRole = $apiToken?->role_context ?? $user->role;
        if (! in_array($effectiveRole, $roles, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak (role tidak sesuai)',
            ], 403);
        }

        // Data user dan role context disimpan ke request.
        // Controller berikutnya bisa membaca data ini tanpa mencari token ulang.
        $request->attributes->set('user_login', $user);
        $request->attributes->set('api_role', $effectiveRole);

        // last_used_at dipakai untuk mengetahui token terakhir digunakan kapan.
        if ($apiToken) {
            DB::table('api_access_tokens')->where('id', $apiToken->id)->update(['last_used_at' => now(), 'updated_at' => now()]);
        }

        // Status online user diperbarui agar admin dapat melihat aktivitas pengguna mobile.
        if (Schema::hasTable('user_login_statuses')) {
            DB::table('user_login_statuses')->updateOrInsert(
                ['user_id' => $user->id],
                [
                    'role' => $user->role,
                    'is_online' => true,
                    'last_seen_at' => now(),
                    'logout_at' => null,
                    'ip_address' => $request->ip(),
                    'user_agent' => substr('Android/API '.$request->userAgent(), 0, 255),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return $next($request);
    }
}
