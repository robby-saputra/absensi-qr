<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Pengumuman</title><link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}"></head>
<body>
@if(($user ?? session('user'))->role === 'piket')
    @include('layouts.sidebar_piket')
@else
    @include('layouts.sidebar_guru')
@endif
<main id="content" class="content">
    <div class="rekap-head"><div><h1>Pengumuman</h1><p>Informasi resmi dari superadmin sekolah.</p></div></div>
    <table><tr><th>Judul</th><th>Kategori</th><th>Periode</th><th>Isi</th></tr>
        @forelse($pengumuman as $p)<tr><td>{{ $p->judul }}</td><td><span class="status-pill">{{ $p->kategori }}</span></td><td>{{ $p->tanggal_mulai ?? '-' }} s/d {{ $p->tanggal_selesai ?? '-' }}</td><td>{{ $p->isi }}</td></tr>@empty
        <tr><td colspan="4" class="empty-row">Belum ada pengumuman aktif.</td></tr>@endforelse
    </table>
</main>
</body>
</html>
