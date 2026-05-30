<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Pengumuman Sistem</title><link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}"></head>
<body>
@include('layouts.sidebar_admin')
<main id="content" class="content">
    <div class="rekap-head"><div><h1>Pengumuman Sistem</h1><p>Buat pengumuman untuk guru, wali kelas, dan guru piket.</p></div><div><a class="btn back" href="/dashboard/admin">Kembali</a><a class="btn" href="/dashboard/admin/pengumuman/create">Tambah Pengumuman</a></div></div>
    <table><tr><th>Judul</th><th>Target</th><th>Kategori</th><th>Periode</th><th>Status</th><th>Aksi</th></tr>
        @forelse($pengumuman as $p)<tr><td>{{ $p->judul }}<br><small>{{ $p->pembuat ?? '-' }}</small></td><td>{{ $p->target_role }}</td><td><span class="status-pill">{{ $p->kategori }}</span></td><td>{{ $p->tanggal_mulai ?? '-' }} s/d {{ $p->tanggal_selesai ?? '-' }}</td><td>{{ $p->aktif ? 'Aktif' : 'Nonaktif' }}</td><td><a class="btn" href="/dashboard/admin/pengumuman/edit/{{ $p->id }}">Edit</a><a class="btn btn-danger confirm-delete" href="/dashboard/admin/pengumuman/delete/{{ $p->id }}">Hapus</a></td></tr>@empty
        <tr><td colspan="6" class="empty-row">Belum ada pengumuman.</td></tr>@endforelse
    </table>
</main>
</body>
</html>
