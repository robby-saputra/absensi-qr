{{-- File ini berisi sidebar guru sebagai menu navigasi utama untuk fitur yang digunakan oleh guru. --}}
<link rel="stylesheet" href="{{ asset('css/pages/layouts-sidebar_admin.css') }}">
<link rel="stylesheet"
    href="{{ asset('css/pages/role-modern.css') }}?v={{ filemtime(public_path('css/pages/role-modern.css')) }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

@php
    $guruSidebarUser = $user ?? session('user');
    $sidebarDutyAssignment = $guruSidebarUser
        ? app(\App\Services\ActiveDutyTeacherResolver::class)->resolve($guruSidebarUser, now('Asia/Jakarta')->toDateString())
        : null;

    if ($guruSidebarUser && !isset($punyaAksesGuruPiket)) {
        $punyaAksesGuruPiket = $sidebarDutyAssignment !== null;
    }

    if ($guruSidebarUser && !isset($isGuruPiketPenggantiAktifHariIni)) {
        $isGuruPiketPenggantiAktifHariIni = $sidebarDutyAssignment && $sidebarDutyAssignment->role !== 'utama';
    }

@endphp

<aside id="sidebar" class="sidebar">
    <div class="logo">
        <img src="{{ asset(\App\Services\AttendanceSettingService::logoSekolah()) }}" alt="Logo" class="brand-logo">
        <span>Guru</span>
        <small>Panel Mapel</small>
    </div>

    <nav class="sidebar-nav" aria-label="Navigasi guru">
        <div class="sidebar-section-title">Utama</div>
        <a href="/dashboard/guru"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        <a href="/dashboard/guru/jadwal"><i class="fa-solid fa-calendar-days"></i> Jadwal Hari Ini</a>
        <a href="/dashboard/guru/status-mengajar"><i class="fa-solid fa-person-chalkboard"></i> Status Mengajar</a>
        <a href="/dashboard/guru/kalender-mengajar"><i class="fa-solid fa-calendar-week"></i> Kalender Mengajar</a>

        <div class="sidebar-section-title">Absensi Mapel</div>
        <button type="button" class="sidebar-parent" data-sidebar-parent>
            <span><i class="fa-solid fa-list-check"></i> Kelola Absensi</span>
            <i class="fa-solid fa-chevron-down sidebar-parent-arrow"></i>
        </button>
        <div class="sidebar-submenu">
            <a href="/dashboard/guru/verifikasi-absensi"><i class="fa-solid fa-list-check"></i> Verifikasi Absen
                Mapel</a>
            <a href="/dashboard/guru/riwayat-absensi"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Absensi</a>
            <a href="/dashboard/guru/pengajuan-izin"><i class="fa-solid fa-file-circle-check"></i> Pengajuan Izin</a>
        </div>

        <div class="sidebar-section-title">Rekap</div>
        <button type="button" class="sidebar-parent" data-sidebar-parent>
            <span><i class="fa-solid fa-chart-column"></i> Laporan</span>
            <i class="fa-solid fa-chevron-down sidebar-parent-arrow"></i>
        </button>
        <div class="sidebar-submenu">
            <a href="/dashboard/guru/rekap-absensi-mapel"><i class="fa-solid fa-qrcode"></i> Rekap Absen Mapel</a>
            <a href="/dashboard/guru/rekap-jadwal"><i class="fa-solid fa-table"></i> Rekap Jadwal</a>
        </div>

        <div class="sidebar-section-title">Akses Lain</div>
        <a href="/dashboard/bantuan?context=guru"><i class="fa-solid fa-circle-question"></i> Pusat Bantuan</a>

        @if ($isWaliKelas ?? false)
            <a href="/dashboard/wali"><i class="fa-solid fa-people-roof"></i> Wali Kelas</a>
        @endif

        @if (($punyaAksesGuruPiket ?? false) || ($isGuruPiketHariIni ?? false) || ($isGuruPiketPenggantiAktifHariIni ?? false))
            <a href="/dashboard/piket"><i class="fa-solid fa-user-shield"></i> Guru Piket Hari Ini</a>
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
@include('layouts.dashboard_clock')
