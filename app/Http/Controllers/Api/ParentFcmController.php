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
        // User dari token dipakai untuk memastikan perangkat didaftarkan oleh pemilik data yang benar.
        $authenticatedUser = $request->attributes->get('user_login');

        // Token FCM adalah token perangkat dari Firebase untuk menerima notifikasi.
        $request->validate([
            'siswa_id' => 'required|exists:users,id',
            'token' => 'required|string|max:500',
            'device_name' => 'nullable|string|max:120',
            'audience' => 'required|in:siswa,orang_tua',
        ]);

        // api_role membedakan apakah token ini milik siswa atau orang tua.
        $roleContext = (string) $request->attributes->get('api_role', '');

        // Sistem memastikan user hanya bisa mendaftarkan perangkat untuk dirinya sendiri.
        if (! $authenticatedUser || ! $this->canRegisterForStudent($authenticatedUser, $roleContext, (int) $request->siswa_id)) {
            return response()->json(['status' => 'error', 'message' => 'Akses token notifikasi ditolak.'], 403);
        }

        // Audience dari request harus sama dengan role context token API.
        if ($roleContext !== $request->audience) {
            return response()->json(['status' => 'error', 'message' => 'Jenis akun notifikasi tidak sesuai.'], 403);
        }

        // Payload ini adalah data yang akan disimpan untuk perangkat penerima notifikasi.
        $payload = [
            'siswa_id' => $request->siswa_id,
            'audience' => $request->audience,
            'device_name' => $request->device_name,
            'last_used_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Kolom dicek dulu agar kode tetap cocok dengan beberapa versi migration.
        if (Schema::hasColumn('parent_fcm_tokens', 'is_active')) {
            $payload['is_active'] = true;
        }

        // Error token lama dibersihkan ketika perangkat berhasil daftar ulang.
        foreach (['last_error_code', 'last_error_message', 'failed_at'] as $column) {
            if (Schema::hasColumn('parent_fcm_tokens', $column)) {
                $payload[$column] = null;
            }
        }

        // updateOrInsert membuat token baru atau memperbarui token lama yang sama.
        DB::table('parent_fcm_tokens')->updateOrInsert(['token' => $request->token], $payload);

        // Log hanya menyimpan potongan token, bukan token penuh, agar lebih aman.
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
        // User hanya boleh mendaftarkan token untuk siswa yang sama dengan token login.
        if ((int) $authenticatedUser->id !== $studentId) {
            return false;
        }

        // Token FCM hanya boleh untuk konteks siswa atau orang tua.
        return in_array($roleContext, ['siswa', 'orang_tua'], true);
    }
}
