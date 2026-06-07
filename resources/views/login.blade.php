<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Absensi QR</title>
    <link rel="stylesheet" href="{{ asset('css/pages/login.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <main class="login-shell">
        <section class="brand-panel">
            <div class="brand-mark">
                <img src="{{ asset(\App\Services\AttendanceSettingService::logoSekolah()) }}" alt="Logo Absensi">
            </div>
            <h1>{{ \App\Services\AttendanceSettingService::namaSekolah() }}</h1>
            <p>Sistem presensi sekolah untuk admin, guru mapel, guru piket, wali kelas, dan siswa.</p>
            <div class="pulse-card">
                <span></span>
                <strong>Monitoring aktif</strong>
                <small>Absensi harian dan mapel tersinkron rapi.</small>
            </div>
        </section>

        <section class="login-card">
            <div class="mobile-logo">
                <img src="{{ asset(\App\Services\AttendanceSettingService::logoSekolah()) }}" alt="Logo Absensi">
            </div>

            <h2>Masuk Sistem</h2>
            <p class="subtitle">Gunakan username dan password akun sekolah.</p>

            <form method="POST" action="/login" id="loginForm">
                @csrf

                <label>
                    Username
                    <input type="text" name="username" required autofocus placeholder="Masukkan username">
                </label>

                <label>
                    Password
                    <input type="password" name="password" required placeholder="Masukkan password">
                </label>

                <button type="submit">Login</button>
            </form>

            <div class="login-help">
                <a href="/bantuan">
                    <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
                    Pusat Bantuan
                </a>
                <small>Pelajari cara memakai sistem web dan aplikasi mobile.</small>
            </div>
        </section>
    </main>

    <script type="application/json" id="login-flash-data">{!! json_encode([
        'success' => session('success'),
        'login_success' => session('login_success'),
        'redirect_to' => session('redirect_to'),
        'error' => session('error'),
        'validation_error' => $errors->first(),
    ]) !!}</script>

    <script>
        const sweetConfig = {
            customClass: {
                popup: 'login-swal',
                confirmButton: 'login-swal-button',
            },
            buttonsStyling: false,
        };

        const loginFlash = JSON.parse(
            document.getElementById('login-flash-data')?.textContent || '{}'
        );

        if (loginFlash.success) {
            Swal.fire({
                ...sweetConfig,
                icon: 'success',
                title: 'Berhasil',
                text: loginFlash.success,
                timer: 2600,
                showConfirmButton: false,
            });
        }

        if (loginFlash.login_success && loginFlash.redirect_to) {
            Swal.fire({
                ...sweetConfig,
                icon: 'success',
                title: 'Login Berhasil',
                text: loginFlash.login_success,
                timer: 1800,
                timerProgressBar: true,
                showConfirmButton: false,
                allowOutsideClick: false,
            }).then(() => {
                window.location.href = loginFlash.redirect_to;
            });
        }

        if (loginFlash.error) {
            Swal.fire({
                ...sweetConfig,
                icon: 'error',
                title: 'Login Gagal',
                text: loginFlash.error,
                confirmButtonText: 'Coba Lagi',
            });
        }

        if (loginFlash.validation_error) {
            Swal.fire({
                ...sweetConfig,
                icon: 'warning',
                title: 'Data Belum Lengkap',
                text: loginFlash.validation_error,
                confirmButtonText: 'Lengkapi',
            });
        }

        document.getElementById('loginForm')?.addEventListener('submit', () => {
            Swal.fire({
                ...sweetConfig,
                title: 'Sedang masuk...',
                text: 'Mohon tunggu sebentar.',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading(),
            });
        });
    </script>

</body>

</html>
