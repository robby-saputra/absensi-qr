<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthWebController extends Controller
{
    public function showLogin()
    {
        // Menampilkan halaman login untuk pengguna website.
        return view('login');
    }

    public function login(Request $request)
    {
        // Username dan password wajib diisi sebelum proses login dilanjutkan.
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // Sistem mencari akun berdasarkan username yang dikirim dari form login.
        $user = User::where('username', $request->username)->first();

        // Jika akun tidak ditemukan atau password salah, login ditolak.
        // Percobaan gagal juga dicatat untuk membantu mendeteksi login mencurigakan.
        if (! $user || ! $this->passwordMatches($request->password, $user->password)) {
            $this->catatLoginMencurigakan($request);

            return back()->with('error', 'Username atau password salah');
        }

        // Akun yang dinonaktifkan admin tidak boleh masuk walaupun password benar.
        if (! (bool) ($user->aktif ?? true)) {
            return back()->with('error', 'Akun Anda sedang dinonaktifkan');
        }

        // Data user disimpan ke session agar halaman website tahu siapa yang sedang login.
        session([
            'user' => $user,
        ]);

        // Status login disimpan agar admin bisa melihat user yang sedang online.
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

        // Jika login berhasil, catatan gagal login sebelumnya dihapus.
        Cache::forget($this->failedLoginKey($request));

        // Admin diarahkan ke dashboard pengelolaan utama.
        if ($user->role === 'admin') {
            return redirect('/login')
                ->with('login_success', 'Login berhasil. Selamat datang, '.$user->nama.'.')
                ->with('redirect_to', '/dashboard/admin');
        }

        // Guru piket diarahkan ke dashboard piket untuk absensi harian.
        if ($user->role === 'piket') {
            return redirect('/login')
                ->with('login_success', 'Login berhasil. Selamat bertugas, '.$user->nama.'.')
                ->with('redirect_to', '/dashboard/piket');
        }

        // Guru mata pelajaran diarahkan ke dashboard guru.
        // Jika guru tersebut juga wali kelas, menu wali kelas tersedia dari role guru.
        if ($user->role === 'guru') {
            return redirect('/login')
                ->with('login_success', 'Login berhasil. Selamat datang, '.$user->nama.'.')
                ->with('redirect_to', '/dashboard/guru');
        }

        // Siswa yang login lewat web diarahkan ke dashboard siswa web.
        if ($user->role === 'siswa') {
            return redirect('/login')
                ->with('login_success', 'Login berhasil. Selamat datang, '.$user->nama.'.')
                ->with('redirect_to', '/dashboard/users');
        }

        return redirect('/login');
    }

    public function logout()
    {
        // Mengambil user dari session untuk memperbarui status online.
        $user = session('user');

        if ($user) {
            // Saat logout, user ditandai offline dan waktu logout dicatat.
            DB::table('user_login_statuses')
                ->where('user_id', $user->id)
                ->update([
                    'is_online' => false,
                    'logout_at' => now(),
                    'last_seen_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        // Session user dihapus agar pengguna tidak lagi dianggap login.
        session()->forget('user');

        return redirect('/login')->with('success', 'Anda berhasil logout.');
    }

    private function passwordMatches(string $plain, ?string $stored): bool
    {
        // Jika password di database kosong, sistem langsung menolak login.
        if (! $stored) {
            return false;
        }

        // Password baru biasanya memakai hash Laravel.
        $isLaravelHash = str_starts_with($stored, '$2y$')
            || str_starts_with($stored, '$2a$')
            || str_starts_with($stored, '$argon2');

        // Sistem tetap mendukung password lama yang masih plain text atau MD5.
        return ($isLaravelHash && Hash::check($plain, $stored))
            || hash_equals($stored, $plain)
            || (strlen($stored) === 32 && hash_equals($stored, md5($plain)));
    }

    private function catatLoginMencurigakan(Request $request): void
    {
        // Key dibuat dari IP dan username agar percobaan gagal bisa dihitung per pengguna/perangkat.
        $key = $this->failedLoginKey($request);
        $total = Cache::increment($key);

        // Catatan gagal login disimpan selama 10 menit.
        Cache::put($key, $total, now()->addMinutes(10));
    }

    private function failedLoginKey(Request $request): string
    {
        // Hash dipakai supaya kombinasi IP dan username tidak disimpan mentah di key cache.
        return 'failed_login:'.sha1($request->ip().'|'.$request->username);
    }
}
