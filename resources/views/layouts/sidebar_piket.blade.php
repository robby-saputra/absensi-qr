<link rel="stylesheet" href="{{ asset('css/pages/layouts-sidebar_admin.css') }}">
<link rel="stylesheet" href="{{ asset('css/pages/role-modern.css') }}?v={{ filemtime(public_path('css/pages/role-modern.css')) }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<aside id="sidebar" class="sidebar">
    <div class="logo">
        <img src="{{ asset(\App\Services\AttendanceSettingService::logoSekolah()) }}" alt="Logo" class="brand-logo">
        <span>Guru Piket</span>
        <small>Panel Absensi</small>
    </div>

    <nav class="sidebar-nav" aria-label="Navigasi guru piket">
        <div class="sidebar-section-title">Utama</div>
        <a href="/dashboard/piket"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>

        <div class="sidebar-section-title">Absensi</div>
        <button type="button" class="sidebar-parent" data-sidebar-parent>
            <span><i class="fa-solid fa-clipboard-list"></i> Kelola Absensi</span>
            <i class="fa-solid fa-chevron-down sidebar-parent-arrow"></i>
        </button>
        <div class="sidebar-submenu">
            <a href="/dashboard/piket/absensi-harian"><i class="fa-solid fa-clipboard-list"></i> Absensi Harian</a>
            <a href="/dashboard/piket/riwayat-absensi"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Absensi</a>
            <a href="/dashboard/piket/pengajuan-izin"><i class="fa-solid fa-file-circle-check"></i> Pengajuan Izin</a>
        </div>

        <div class="sidebar-section-title">Piket</div>
        <button type="button" class="sidebar-parent" data-sidebar-parent>
            <span><i class="fa-solid fa-user-shield"></i> Jadwal & QR</span>
            <i class="fa-solid fa-chevron-down sidebar-parent-arrow"></i>
        </button>
        <div class="sidebar-submenu">
            <a href="/dashboard/piket/rekap-jadwal"><i class="fa-solid fa-user-shield"></i> Rekap Jadwal Piket</a>
            <a href="/dashboard/piket/qr-harian"><i class="fa-solid fa-qrcode"></i> QR Harian</a>
        </div>

        <div class="sidebar-section-title">Komunikasi</div>
        <button type="button" class="sidebar-parent" data-sidebar-parent>
            <span><i class="fa-solid fa-message"></i> Informasi</span>
            <i class="fa-solid fa-chevron-down sidebar-parent-arrow"></i>
        </button>
        <div class="sidebar-submenu">
            <a href="/dashboard/notifikasi-saya"><i class="fa-solid fa-bell"></i> Notifikasi Saya</a>
            <a href="/dashboard/riwayat-perubahan-saya"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Perubahan</a>
            <a href="/dashboard/pesan-internal"><i class="fa-solid fa-message"></i> Pesan Internal</a>
            <a href="/dashboard/delegasi-sementara"><i class="fa-solid fa-user-clock"></i> Delegasi</a>
            <a href="/dashboard/pengumuman"><i class="fa-solid fa-bullhorn"></i> Pengumuman</a>
        </div>

        <div class="sidebar-section-title">Akses Lain</div>
        <a href="/dashboard/bantuan?context=piket"><i class="fa-solid fa-circle-question"></i> Pusat Bantuan</a>

        @if(($user ?? session('user'))?->role === 'guru')
            <a href="/dashboard/guru"><i class="fa-solid fa-chalkboard-user"></i> Guru Mapel</a>
        @endif

        <a class="sidebar-logout" href="/logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>
</aside>

<button class="toggle-btn" type="button" data-toggle-sidebar aria-label="Toggle sidebar">
    <i class="fa-solid fa-bars"></i>
    <span>Menu</span>
</button>

<script src="{{ asset('js/app-ui.js') }}"></script>

@include('layouts.alerts')
