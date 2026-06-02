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
        $header = $request->header('Authorization');

        if (! $header) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token tidak ditemukan',
            ], 401);
        }

        $token = str_replace('Bearer ', '', $header);

        $user = User::where('remember_token', $token)->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token tidak valid',
            ], 401);
        }

        if (! (bool) ($user->aktif ?? true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun sedang dinonaktifkan',
            ], 403);
        }

        if (! in_array($user->role, $roles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak (role tidak sesuai)',
            ], 403);
        }

        // 🔥 SIMPAN FULL USER (BUKAN ID SAJA)
        $request->attributes->set('user_login', $user);

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
