<link rel="stylesheet" href="{{ asset('css/pages/layouts-sidebar_admin.css') }}">
<link rel="stylesheet"
    href="{{ asset('css/pages/role-modern.css') }}?v={{ filemtime(public_path('css/pages/role-modern.css')) }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

@php
    $waliUser = $user ?? session('user');

    if ($waliUser && !isset($isGuruMapelHariIni)) {
        $isGuruMapelHariIni = DB::table('jadwal_pelajarans')
            ->where('hari', now()->locale('id')->isoFormat('dddd'))
            ->where('guru_id', $waliUser->id)
            ->exists();
    }

    if ($waliUser && !isset($isGuruPiketHariIni)) {
        $isGuruPiketHariIni = DB::table('guru_pikets')
            ->where('guru_id', $waliUser->id)
            ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
            ->where('aktif', 1)
            ->exists();
    }

    if ($waliUser && !isset($punyaAksesGuruPiket)) {
        $punyaAksesGuruPiket = DB::table('guru_pikets')
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->where('guru_id', $waliUser->id)
            ->exists();
    }
@endphp

<aside id="sidebar" class="sidebar">
    <div class="logo">
        <img src="{{ asset(\App\Services\AttendanceSettingService::logoSekolah()) }}" alt="Logo" class="brand-logo">
        <span>Wali Kelas</span>
        <small>Panel Kelas</small>
    </div>

    <nav class="sidebar-nav" aria-label="Navigasi wali kelas">
        <div class="sidebar-section-title">Utama</div>
        <a href="/dashboard/wali"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>

        <div class="sidebar-section-title">Kelas</div>
        <button type="button" class="sidebar-parent" data-sidebar-parent>
            <span><i class="fa-solid fa-people-roof"></i> Kelola Kelas</span>
            <i class="fa-solid fa-chevron-down sidebar-parent-arrow"></i>
        </button>
        <div class="sidebar-submenu">
            <a href="/dashboard/wali/siswa"><i class="fa-solid fa-user-graduate"></i> Data Siswa</a>
            <a href="/dashboard/wali/absensi"><i class="fa-solid fa-clipboard-list"></i> Absensi Siswa</a>
        </div>

        <div class="sidebar-section-title">Komunikasi</div>
        <button type="button" class="sidebar-parent" data-sidebar-parent>
            <span><i class="fa-solid fa-message"></i> Informasi</span>
            <i class="fa-solid fa-chevron-down sidebar-parent-arrow"></i>
        </button>
        <div class="sidebar-submenu">
            <a href="/dashboard/notifikasi-saya"><i class="fa-solid fa-bell"></i> Notifikasi Saya</a>
        </div>

        <div class="sidebar-section-title">Akses Lain</div>
        <a href="/dashboard/bantuan?context=wali"><i class="fa-solid fa-circle-question"></i> Pusat Bantuan</a>

        @if ($isGuruMapelHariIni ?? false)
            <a href="/dashboard/guru"><i class="fa-solid fa-chalkboard-user"></i> Guru Mapel</a>
        @else
            <a class="disabled-link" href="#" aria-disabled="true"><i class="fa-solid fa-chalkboard-user"></i>
                Guru Mapel</a>
        @endif

        @if (($punyaAksesGuruPiket ?? false) || ($isGuruPiketHariIni ?? false))
            <a href="/dashboard/piket"><i class="fa-solid fa-user-shield"></i> Guru Piket</a>
        @else
            <a class="disabled-link" href="#" aria-disabled="true"><i class="fa-solid fa-user-shield"></i> Guru
                Piket</a>
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
