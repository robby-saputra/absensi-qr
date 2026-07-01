{{-- File ini menampilkan detail jurusan agar admin dapat melihat informasi jurusan dan data terkait secara lengkap. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Jurusan {{ $jurusan->kode_jurusan }}</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-jurusan-index.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-jurusan-detail.css') }}">
</head>
<body>
    @include('layouts.sidebar_admin')
    <main id="content" class="content"><div class="major-page">
        <section class="major-hero"><div><span>Detail Program Keahlian</span><h1>{{ $jurusan->nama_jurusan }}</h1><p>Kode jurusan: {{ $jurusan->kode_jurusan }}</p></div><div class="hero-actions"><a href="/dashboard/admin/jurusan" class="btn ghost">Kembali</a><a href="/dashboard/admin/jurusan/edit/{{ $jurusan->id }}" class="btn primary">Edit Jurusan</a></div></section>
        <section class="summary-grid"><div><span>Total Kelas</span><strong>{{ $kelas->count() }}</strong></div><div><span>Total Siswa Aktif</span><strong>{{ $kelas->sum('jumlah_siswa') }}</strong></div><div><span>Kelas Memiliki Wali</span><strong>{{ $kelas->whereNotNull('wali_kelas_id')->count() }}</strong></div><div><span>Kelas Tanpa Wali</span><strong>{{ $kelas->whereNull('wali_kelas_id')->count() }}</strong></div></section>
        <section class="major-panel"><div class="panel-head"><span>Daftar Kelas</span><h2>Kelas di Jurusan {{ $jurusan->kode_jurusan }}</h2><p>Informasi wali kelas dan jumlah siswa aktif.</p></div>
            <div class="class-detail-grid">@forelse($kelas as $k)<article class="class-detail-card"><div class="class-detail-head"><div><span>Kelas</span><h3>{{ $k->nama_kelas }}</h3></div><b>{{ $k->jumlah_siswa }} siswa</b></div><div class="class-detail-info"><span>Wali Kelas</span><strong>{{ $k->nama_wali ?? 'Belum ditentukan' }}</strong></div><footer><a href="/dashboard/admin/kelas/edit/{{ $k->id }}" class="card-btn edit">Edit Kelas</a></footer></article>@empty<div class="empty-state"><strong>Belum ada kelas</strong><span>Jurusan ini belum digunakan oleh kelas mana pun.</span></div>@endforelse</div>
        </section>
    </div></main>
</body></html>
