<link rel="stylesheet" href="{{ asset('css/pages/layouts-sidebar_admin.css') }}">
<link rel="stylesheet" href="{{ asset('css/pages/admin-global.css') }}">
<link rel="stylesheet"
    href="{{ asset('css/pages/role-modern.css') }}?v={{ filemtime(public_path('css/pages/role-modern.css')) }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<aside id="sidebar" class="sidebar">
    <div class="logo">
        <img src="{{ asset(\App\Services\AttendanceSettingService::logoSekolah()) }}" alt="Logo" class="brand-logo">
        <span>{{ \App\Services\AttendanceSettingService::namaSekolah() }}</span>
        <small>Panel Admin</small>
    </div>

    <nav class="sidebar-nav" aria-label="Navigasi admin">
        <div class="sidebar-section-title">Utama</div>
        <a href="/dashboard/admin"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>

        <div class="sidebar-section-title">Data Master</div>
        <button type="button" class="sidebar-parent" data-sidebar-parent>
            <span><i class="fa-solid fa-users"></i> Pengguna</span>
            <i class="fa-solid fa-chevron-down sidebar-parent-arrow"></i>
        </button>
        <div class="sidebar-submenu">
            <a href="/dashboard/admin/siswa"><i class="fa-solid fa-user-graduate"></i> Siswa</a>
            <a href="/dashboard/admin/guru"><i class="fa-solid fa-chalkboard-user"></i> Guru</a>
        </div>

        <button type="button" class="sidebar-parent" data-sidebar-parent>
            <span><i class="fa-solid fa-school"></i> Kelas & Petugas</span>
            <i class="fa-solid fa-chevron-down sidebar-parent-arrow"></i>
        </button>
        <div class="sidebar-submenu">
            <a href="/dashboard/admin/kelas"><i class="fa-solid fa-school"></i> Kelas</a>
            <a href="/dashboard/admin/jurusan"><i class="fa-solid fa-layer-group"></i> Jurusan</a>
            <a href="/dashboard/admin/wali-kelas"><i class="fa-solid fa-people-roof"></i> Wali Kelas</a>
            <a href="/dashboard/admin/guru-piket"><i class="fa-solid fa-user-shield"></i> Guru Piket</a>
        </div>

        <div class="sidebar-section-title">Jadwal</div>
        <button type="button" class="sidebar-parent" data-sidebar-parent>
            <span><i class="fa-solid fa-calendar-days"></i> Jadwal</span>
            <i class="fa-solid fa-chevron-down sidebar-parent-arrow"></i>
        </button>
        <div class="sidebar-submenu">
            <a href="/dashboard/admin/jadwal"><i class="fa-solid fa-calendar-days"></i> Jadwal</a>
            <a href="/dashboard/admin/kalender-sekolah"><i class="fa-solid fa-calendar-xmark"></i> Hari Libur</a>
        </div>

        <div class="sidebar-section-title">Absensi</div>
        <button type="button" class="sidebar-parent" data-sidebar-parent>
            <span><i class="fa-solid fa-clipboard-user"></i> Rekap Absensi</span>
            <i class="fa-solid fa-chevron-down sidebar-parent-arrow"></i>
        </button>
        <div class="sidebar-submenu">
            <a href="/dashboard/admin/absensi/rekap"><i class="fa-solid fa-clipboard-list"></i> Rekap Absensi Harian</a>
            <a href="/dashboard/admin/rekap/absensi-mapel"><i class="fa-solid fa-list-check"></i> Rekap Absensi
                Mapel</a>
            <a href="/dashboard/admin/pengajuan-izin"><i class="fa-solid fa-file-circle-check"></i> Pengajuan Izin</a>
        </div>

        <div class="sidebar-section-title">Pengaturan</div>
        <a href="/dashboard/admin/pengaturan"><i class="fa-solid fa-gear"></i> Pengaturan Absensi</a>
        <a href="/dashboard/admin/arsip"><i class="fa-solid fa-box-archive"></i> Arsip Data</a>
        <a href="/dashboard/bantuan?context=admin"><i class="fa-solid fa-circle-question"></i> Pusat Bantuan</a>
        <a class="sidebar-logout" href="/logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>
</aside>

<button class="toggle-btn" type="button" data-toggle-sidebar aria-label="Toggle sidebar">
    <i class="fa-solid fa-bars"></i>
    <span>Menu</span>
</button>

@include('layouts.alerts')
@include('layouts.dashboard_clock')

<script src="{{ asset('js/app-ui.js') }}"></script>
