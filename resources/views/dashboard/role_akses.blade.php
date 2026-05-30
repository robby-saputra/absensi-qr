<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Role & Akses</title><link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}"></head><body>
@include('layouts.sidebar_admin')
<main id="content" class="content"><div class="rekap-head"><div><h1>Manajemen Role & Akses</h1><p>Pemantauan relasi peran tanpa membuka akses superadmin baru.</p></div><a class="btn back" href="/dashboard/admin">Kembali</a></div>
<div class="rekap-filter"><span class="status-pill">Siswa aktif: {{ $siswaAktif }}</span><span class="status-pill">Siswa nonaktif: {{ $siswaNonaktif }}</span><span class="status-pill">Guru wali: {{ $guruWali->count() }}</span><span class="status-pill">Guru piket: {{ $guruPiket->count() }}</span></div>
<h2>Guru yang juga Wali Kelas</h2><table><tr><th>Nama</th><th>Username</th></tr>@forelse($guruWali as $g)<tr><td>{{ $g->nama }}</td><td>{{ $g->username }}</td></tr>@empty<tr><td colspan="2" class="empty-row">Tidak ada.</td></tr>@endforelse</table>
<h2>Guru yang juga Guru Piket</h2><table><tr><th>Nama</th><th>Username</th></tr>@forelse($guruPiket as $g)<tr><td>{{ $g->nama }}</td><td>{{ $g->username }}</td></tr>@empty<tr><td colspan="2" class="empty-row">Tidak ada.</td></tr>@endforelse</table>
<h2>Akun Tanpa Riwayat Login</h2><table><tr><th>Nama</th><th>Role</th><th>Username</th></tr>@forelse($akunTanpaLogin as $a)<tr><td>{{ $a->nama }}</td><td>{{ $a->role }}</td><td>{{ $a->username }}</td></tr>@empty<tr><td colspan="3" class="empty-row">Semua akun sudah pernah login.</td></tr>@endforelse</table>
</main></body></html>
