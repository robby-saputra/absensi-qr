<link rel="stylesheet" href="{{ asset('css/pages/layouts-sidebar_admin.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<aside id="sidebar" class="sidebar">
    <div class="logo">
        <span>Absensi</span>
        <small>Admin Panel</small>
    </div>

    <nav class="sidebar-nav" aria-label="Navigasi admin">
        <a href="/dashboard/admin"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        <a href="/dashboard/admin/siswa"><i class="fa-solid fa-user-graduate"></i> Siswa</a>
        <a href="/dashboard/admin/siswa/import"><i class="fa-solid fa-file-import"></i> Import Siswa</a>
        <a href="/dashboard/admin/guru"><i class="fa-solid fa-chalkboard-user"></i> Guru</a>
        <a href="/dashboard/admin/wali-kelas"><i class="fa-solid fa-people-roof"></i> Wali Kelas</a>
        <a href="/dashboard/admin/guru-piket"><i class="fa-solid fa-user-shield"></i> Guru Piket</a>
        <a href="/dashboard/admin/kelas"><i class="fa-solid fa-school"></i> Kelas</a>
        <a href="/dashboard/admin/jurusan"><i class="fa-solid fa-layer-group"></i> Jurusan</a>
        <a href="/dashboard/admin/jadwal"><i class="fa-solid fa-calendar-days"></i> Jadwal</a>
        <a href="/dashboard/admin/absensi/rekap"><i class="fa-solid fa-clipboard-list"></i> Rekap Absensi</a>
        <a href="/logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>
</aside>

<button class="toggle-btn" type="button" data-toggle-sidebar aria-label="Toggle sidebar">
    <i class="fa-solid fa-bars"></i>
    <span>Menu</span>
</button>

<script src="{{ asset('js/app-ui.js') }}"></script>
