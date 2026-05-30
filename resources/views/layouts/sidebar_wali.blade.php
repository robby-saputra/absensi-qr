<link rel="stylesheet" href="{{ asset('css/pages/layouts-sidebar_admin.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

@php
    $waliUser = $user ?? session('user');

    if ($waliUser && !isset($isGuruMapelHariIni)) {
        $isGuruMapelHariIni = DB::table('jadwal_pelajarans')
            ->where('hari', now()->locale('id')->isoFormat('dddd'))
            ->where(function ($query) use ($waliUser) {
                $query->where('guru_id', $waliUser->id)
                    ->orWhere(function ($pengganti) use ($waliUser) {
                        $pengganti->where('guru_pengganti_id', $waliUser->id)
                            ->where('status_guru', 'digantikan');
                    });
            })
            ->exists();
    }

    if ($waliUser && !isset($isGuruPiketHariIni)) {
        $isGuruPiketHariIni = DB::table('guru_pikets')
            ->where('guru_id', $waliUser->id)
            ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
            ->where('aktif', 1)
            ->exists();
    }

    if ($waliUser && !isset($isGuruPiketPenggantiHariIni)) {
        $isGuruPiketPenggantiHariIni = DB::table('guru_pikets')
            ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
            ->where('aktif', 1)
            ->whereIn('status', ['Izin', 'Sakit'])
            ->where(function ($query) use ($waliUser) {
                $query->where('guru_pengganti_id', $waliUser->id)
                    ->orWhere('guru_pengganti2_id', $waliUser->id);
            })
            ->exists();
    }
@endphp

<aside id="sidebar" class="sidebar">
    <div class="logo">
        <img src="{{ asset(\App\Services\AttendanceSettingService::logoSekolah()) }}" alt="Logo" class="brand-logo">
        <span>Wali Kelas</span>
        <small>Class Panel</small>
    </div>

    <nav class="sidebar-nav" aria-label="Navigasi wali kelas">
        <a href="/dashboard/wali"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        <a href="/dashboard/wali/siswa"><i class="fa-solid fa-user-graduate"></i> Data Siswa</a>
        <a href="/dashboard/wali/absensi"><i class="fa-solid fa-clipboard-list"></i> Absensi Siswa</a>
        <a href="/dashboard/validasi-tutup-bulan"><i class="fa-solid fa-clipboard-check"></i> Validasi Bulanan</a>
        <a href="/dashboard/notifikasi-saya"><i class="fa-solid fa-bell"></i> Notifikasi Saya</a>
        <a href="/dashboard/riwayat-perubahan-saya"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Perubahan</a>
        <a href="/dashboard/pesan-internal"><i class="fa-solid fa-message"></i> Pesan Internal</a>
        <a href="/dashboard/delegasi-sementara"><i class="fa-solid fa-user-clock"></i> Delegasi</a>
        <a href="/dashboard/pengumuman"><i class="fa-solid fa-bullhorn"></i> Pengumuman</a>

        @if(($isGuruMapelHariIni ?? false))
            <a href="/dashboard/guru"><i class="fa-solid fa-chalkboard-user"></i> Guru Mapel</a>
        @else
            <a class="disabled-link" href="#" aria-disabled="true"><i class="fa-solid fa-chalkboard-user"></i> Guru Mapel</a>
        @endif

        @if(($isGuruPiketHariIni ?? false) || ($isGuruPiketPenggantiHariIni ?? false))
            <a href="/dashboard/piket"><i class="fa-solid fa-user-shield"></i> Guru Piket</a>
        @else
            <a class="disabled-link" href="#" aria-disabled="true"><i class="fa-solid fa-user-shield"></i> Guru Piket</a>
        @endif

        <a href="/logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>
</aside>

<button class="toggle-btn" type="button" data-toggle-sidebar aria-label="Toggle sidebar">
    <i class="fa-solid fa-bars"></i>
    <span>Menu</span>
</button>

<style>
.disabled-link{opacity:.45;pointer-events:none;}
</style>

<script src="{{ asset('js/app-ui.js') }}"></script>

@include('layouts.alerts')
