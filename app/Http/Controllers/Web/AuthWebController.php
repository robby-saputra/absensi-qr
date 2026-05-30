<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AuthWebController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('username', $request->username)->first();

        if (! $user || ! $this->passwordMatches($request->password, $user->password)) {
            $this->catatLoginMencurigakan($request);
            return back()->with('error', 'Username atau password salah');
        }

        if (! (bool) ($user->aktif ?? true)) {
            return back()->with('error', 'Akun Anda sedang dinonaktifkan');
        }

        session([
            'user' => $user,
        ]);

        DB::table('user_login_statuses')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'role' => $user->role,
                'is_online' => true,
                'login_at' => now(),
                'last_seen_at' => now(),
                'logout_at' => null,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        Cache::forget($this->failedLoginKey($request));

        // ADMIN
        if ($user->role === 'admin') {
            return redirect('/login')
                ->with('login_success', 'Login berhasil. Selamat datang, '.$user->nama.'.')
                ->with('redirect_to', '/dashboard/admin');
        }

        // PIKET
        if ($user->role === 'piket') {
            return redirect('/login')
                ->with('login_success', 'Login berhasil. Selamat bertugas, '.$user->nama.'.')
                ->with('redirect_to', '/dashboard/piket');
        }

        // GURU MAPEL / WALI KELAS
        if ($user->role === 'guru') {
            return redirect('/login')
                ->with('login_success', 'Login berhasil. Selamat datang, '.$user->nama.'.')
                ->with('redirect_to', '/dashboard/guru');
        }

        // SISWA
        if ($user->role === 'siswa') {
            return redirect('/login')
                ->with('login_success', 'Login berhasil. Selamat datang, '.$user->nama.'.')
                ->with('redirect_to', '/dashboard/users');
        }

        return redirect('/login');
    }

    public function logout()
    {
        $user = session('user');

        if ($user) {
            DB::table('user_login_statuses')
                ->where('user_id', $user->id)
                ->update([
                    'is_online' => false,
                    'logout_at' => now(),
                    'last_seen_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        session()->forget('user');

        return redirect('/login')->with('success', 'Anda berhasil logout.');
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

    private function catatLoginMencurigakan(Request $request): void
    {
        $key = $this->failedLoginKey($request);
        $total = Cache::increment($key);
        Cache::put($key, $total, now()->addMinutes(10));
        $threshold = (int) (DB::table('attendance_settings')->where('key', 'notif_login_threshold')->value('value') ?: 3);
        $notifLogin = (DB::table('attendance_settings')->where('key', 'notif_login_mencurigakan')->value('value') ?? '1') === '1';

        if (Schema::hasTable('login_security_events')) {
            DB::table('login_security_events')->insert([
                'username' => $request->username,
                'user_id' => User::where('username', $request->username)->value('id'),
                'event_type' => $total >= $threshold ? 'suspicious' : 'failed',
                'attempt_count' => $total,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'keterangan' => $total >= $threshold ? 'Percobaan login gagal berulang.' : 'Login gagal.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! $notifLogin || $total < $threshold || ! Schema::hasTable('notifications')) {
            return;
        }

        $lockKey = 'notif_login_mencurigakan:'.sha1($request->ip().'|'.$request->username);
        if (! Cache::add($lockKey, true, now()->addMinutes(10))) {
            return;
        }

        $payload = [
            'user_id' => null,
            'judul' => 'Login Mencurigakan',
            'pesan' => 'Ada '.$total.' percobaan login gagal untuk username '.$request->username.' dari IP '.$request->ip().'.',
            'status' => 'belum_dibaca',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        foreach ([
            'kategori' => 'login_mencurigakan',
            'severity' => 'danger',
            'source_type' => 'auth',
            'source_id' => null,
            'payload' => json_encode([
                'username' => $request->username,
                'ip_address' => $request->ip(),
                'total_gagal' => $total,
                'waktu' => now()->format('Y-m-d H:i:s'),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ], JSON_UNESCAPED_UNICODE),
        ] as $column => $value) {
            if (Schema::hasColumn('notifications', $column)) {
                $payload[$column] = $value;
            }
        }

        DB::table('notifications')->insert($payload);
    }

    private function failedLoginKey(Request $request): string
    {
        return 'failed_login:'.sha1($request->ip().'|'.$request->username);
    }
}
