<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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

        $roleContext = (string) $request->attributes->get('api_role', '');
        if (! $authenticatedUser || ! $this->canRegisterForStudent($authenticatedUser, $roleContext, (int) $request->siswa_id)) {
            return response()->json(['status' => 'error', 'message' => 'Akses token notifikasi ditolak.'], 403);
        }

        if ($roleContext !== $request->audience) {
            return response()->json(['status' => 'error', 'message' => 'Jenis akun notifikasi tidak sesuai.'], 403);
        }

        $payload = [
            'siswa_id' => $request->siswa_id,
            'audience' => $request->audience,
            'device_name' => $request->device_name,
            'last_used_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('parent_fcm_tokens', 'is_active')) {
            $payload['is_active'] = true;
        }

        foreach (['last_error_code', 'last_error_message', 'failed_at'] as $column) {
            if (Schema::hasColumn('parent_fcm_tokens', $column)) {
                $payload[$column] = null;
            }
        }

        DB::table('parent_fcm_tokens')->updateOrInsert(['token' => $request->token], $payload);

        Log::info('FCM token perangkat tersimpan', [
            'siswa_id' => (int) $request->siswa_id,
            'audience' => $request->audience,
            'token_suffix' => fcmTokenSuffix((string) $request->token),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Token notifikasi perangkat tersimpan.',
        ]);
    }

    private function canRegisterForStudent(object $authenticatedUser, string $roleContext, int $studentId): bool
    {
        if ((int) $authenticatedUser->id !== $studentId) {
            return false;
        }

        return in_array($roleContext, ['siswa', 'orang_tua'], true);
    }
}
