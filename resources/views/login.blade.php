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

    <script>
        const sweetConfig = {
            customClass: {
                popup: 'login-swal',
                confirmButton: 'login-swal-button',
            },
            buttonsStyling: false,
        };

        @if (session('success'))
            Swal.fire({
                ...sweetConfig,
                icon: 'success',
                title: 'Berhasil',
                text: @json(session('success')),
                timer: 2600,
                showConfirmButton: false,
            });
        @endif

        @if (session('login_success') && session('redirect_to'))
            Swal.fire({
                ...sweetConfig,
                icon: 'success',
                title: 'Login Berhasil',
                text: @json(session('login_success')),
                timer: 1800,
                timerProgressBar: true,
                showConfirmButton: false,
                allowOutsideClick: false,
            }).then(() => {
                window.location.href = @json(session('redirect_to'));
            });
        @endif

        @if (session('error'))
            Swal.fire({
                ...sweetConfig,
                icon: 'error',
                title: 'Login Gagal',
                text: @json(session('error')),
                confirmButtonText: 'Coba Lagi',
            });
        @endif

        @if ($errors->any())
            Swal.fire({
                ...sweetConfig,
                icon: 'warning',
                title: 'Data Belum Lengkap',
                text: @json($errors->first()),
                confirmButtonText: 'Lengkapi',
            });
        @endif

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
