<link rel="stylesheet" href="{{ asset('css/pages/layouts-sidebar_admin.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<aside id="sidebar" class="sidebar">
    <div class="logo">
        <img src="{{ asset(\App\Services\AttendanceSettingService::logoSekolah()) }}" alt="Logo" class="brand-logo">
        <span>Guru Piket</span>
        <small>Attendance Panel</small>
    </div>

    <nav class="sidebar-nav" aria-label="Navigasi guru piket">
        <a href="/dashboard/piket"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        <a href="/dashboard/piket/absensi-harian"><i class="fa-solid fa-clipboard-list"></i> Absensi Harian</a>
        <a href="/dashboard/piket/riwayat-absensi"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Absensi</a>
        <a href="/dashboard/piket/rekap-jadwal"><i class="fa-solid fa-user-shield"></i> Rekap Jadwal Piket</a>
        <a href="/dashboard/piket/qr-harian"><i class="fa-solid fa-qrcode"></i> QR Harian</a>

        @if(($user ?? session('user'))?->role === 'guru')
            <a href="/dashboard/guru"><i class="fa-solid fa-chalkboard-user"></i> Guru Mapel</a>
        @endif

        <a href="/logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>
</aside>

<button class="toggle-btn" type="button" data-toggle-sidebar aria-label="Toggle sidebar">
    <i class="fa-solid fa-bars"></i>
    <span>Menu</span>
</button>

<script src="{{ asset('js/app-ui.js') }}"></script>

@include('layouts.alerts')
