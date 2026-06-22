<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HeartbeatController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = session('user');

        if (! $user) {
            return response()->json(['ok' => false], 401);
        }

        DB::table('user_login_statuses')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'role' => $user->role,
                'is_online' => true,
                'last_seen_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json(['ok' => true]);
    }
}
