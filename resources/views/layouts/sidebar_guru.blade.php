<link rel="stylesheet" href="{{ asset('css/pages/layouts-sidebar_admin.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<aside id="sidebar" class="sidebar">
    <div class="logo">
        <span>Guru</span>
        <small>Mapel Panel</small>
    </div>

    <nav class="sidebar-nav" aria-label="Navigasi guru">
        <a href="/dashboard/guru"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        <a href="/dashboard/guru/jadwal"><i class="fa-solid fa-calendar-days"></i> Jadwal Hari Ini</a>
        <a href="/dashboard/guru/verifikasi-absensi"><i class="fa-solid fa-list-check"></i> Verifikasi Absensi</a>
        <a href="/dashboard/guru/riwayat-absensi"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Absensi</a>

        @if(($isWaliKelas ?? false))
            <a href="/dashboard/wali"><i class="fa-solid fa-people-roof"></i> Wali Kelas</a>
        @endif

        @if(($isGuruPiketHariIni ?? false) || ($isGuruPiketPenggantiHariIni ?? false))
            <a href="/dashboard/piket"><i class="fa-solid fa-user-shield"></i> Guru Piket</a>
        @endif

        <a href="/logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>
</aside>

<button class="toggle-btn" type="button" data-toggle-sidebar aria-label="Toggle sidebar">
    <i class="fa-solid fa-bars"></i>
    <span>Menu</span>
</button>

<script src="{{ asset('js/app-ui.js') }}"></script>
