<link rel="stylesheet" href="{{ asset('css/pages/layouts-sidebar_admin.css') }}">
<link rel="stylesheet"
    href="{{ asset('css/pages/role-modern.css') }}?v={{ filemtime(public_path('css/pages/role-modern.css')) }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

@php
    $guruSidebarUser = $user ?? session('user');

    if ($guruSidebarUser && !isset($punyaAksesGuruPiket)) {
        $punyaAksesGuruPiket = DB::table('guru_pikets')
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->where('guru_id', $guruSidebarUser->id)
            ->exists();
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
            <a href="/dashboard/guru/rekap-siswa"><i class="fa-solid fa-user-graduate"></i> Rekap Siswa</a>
            <a href="/dashboard/guru/rekap-absensi"><i class="fa-solid fa-clipboard-list"></i> Rekap Absensi</a>
            <a href="/dashboard/guru/rekap-absensi-mapel"><i class="fa-solid fa-qrcode"></i> Rekap Absen Mapel</a>
            <a href="/dashboard/guru/rekap-jadwal"><i class="fa-solid fa-table"></i> Rekap Jadwal</a>
        </div>

        <div class="sidebar-section-title">Komunikasi</div>
        <button type="button" class="sidebar-parent" data-sidebar-parent>
            <span><i class="fa-solid fa-message"></i> Informasi</span>
            <i class="fa-solid fa-chevron-down sidebar-parent-arrow"></i>
        </button>
        <div class="sidebar-submenu">
            <a href="/dashboard/notifikasi-saya"><i class="fa-solid fa-bell"></i> Notifikasi Saya</a>
            <a href="/dashboard/riwayat-perubahan-saya"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat
                Perubahan</a>
            <a href="/dashboard/pesan-internal"><i class="fa-solid fa-message"></i> Pesan Internal</a>
            <a href="/dashboard/pengumuman"><i class="fa-solid fa-bullhorn"></i> Pengumuman</a>
        </div>

        <div class="sidebar-section-title">Akses Lain</div>
        <a href="/dashboard/bantuan?context=guru"><i class="fa-solid fa-circle-question"></i> Pusat Bantuan</a>

        @if ($isWaliKelas ?? false)
            <a href="/dashboard/wali"><i class="fa-solid fa-people-roof"></i> Wali Kelas</a>
        @endif

        @if (($punyaAksesGuruPiket ?? false) || ($isGuruPiketHariIni ?? false))
            <a href="/dashboard/piket"><i class="fa-solid fa-user-shield"></i> Guru Piket</a>
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
