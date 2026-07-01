{{-- File ini menampilkan daftar jurusan sebagai data master yang digunakan untuk mengelompokkan kelas dan siswa. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jurusan</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-jurusan-index.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-jurusan-detail.css') }}">
</head>
<body>
    @include('layouts.sidebar_admin')
    <main id="content" class="content">
        <div class="major-page">
            @include('layouts.alerts')
            <section class="major-hero">
                <div><span>Data Akademik</span><h1>Kelola Jurusan</h1><p>Atur program keahlian serta pantau kelas dan siswa di setiap jurusan.</p></div>
                <div class="hero-actions"><a href="/dashboard/admin" class="btn ghost">Kembali</a><a href="/dashboard/admin/pdf/jurusan" target="_blank" class="btn soft">PDF Resmi</a><a href="/dashboard/admin/jurusan/create" class="btn primary">＋ Tambah Jurusan</a></div>
            </section>

            <section class="summary-grid">
                <div><span>Total Jurusan</span><strong>{{ $jurusan->count() }}</strong></div>
                <div><span>Total Kelas</span><strong>{{ $jurusan->sum('jumlah_kelas') }}</strong></div>
                <div><span>Total Siswa Aktif</span><strong>{{ $jurusan->sum('jumlah_siswa') }}</strong></div>
                <div><span>Belum Memiliki Kelas</span><strong>{{ $jurusan->where('jumlah_kelas',0)->count() }}</strong></div>
            </section>

            <section class="major-panel">
                <div class="panel-head"><div><span>Daftar Jurusan</span><h2>Program Keahlian</h2><p>{{ $jurusan->count() }} data ditemukan</p></div></div>
                <form method="GET" class="major-filter">
                    <label class="search"><span>Pencarian</span><input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama atau kode jurusan..."></label>
                    <label><span>Status Kelas</span><select name="kelas_status"><option value="">Semua status</option><option value="terpakai" {{ ($filters['kelas_status'] ?? '') === 'terpakai' ? 'selected' : '' }}>Memiliki kelas</option><option value="kosong" {{ ($filters['kelas_status'] ?? '') === 'kosong' ? 'selected' : '' }}>Belum memiliki kelas</option></select></label>
                    <label><span>Urutkan</span><select name="sort"><option value="nama" {{ ($filters['sort'] ?? 'nama') === 'nama' ? 'selected' : '' }}>Nama jurusan</option><option value="kode" {{ ($filters['sort'] ?? '') === 'kode' ? 'selected' : '' }}>Kode jurusan</option><option value="terbaru" {{ ($filters['sort'] ?? '') === 'terbaru' ? 'selected' : '' }}>Terbaru</option></select></label>
                    <div class="filter-actions"><button class="btn apply" type="submit">Terapkan</button><a class="btn reset" href="/dashboard/admin/jurusan">Reset</a></div>
                </form>

                <div class="major-grid">
                    @forelse($jurusan as $j)
                        @php
                            $initial = strtoupper(substr($j->kode_jurusan ?: $j->nama_jurusan,0,3));
                            $colorIndex = ((int) $j->id % 4) + 1;
                        @endphp
                        <article class="major-card color-{{ $colorIndex }}">
                            <header><div class="major-code">{{ $initial }}</div><span class="usage {{ $j->jumlah_kelas > 0 ? 'active' : 'empty' }}">{{ $j->jumlah_kelas > 0 ? 'Aktif digunakan' : 'Belum digunakan' }}</span></header>
                            <div class="major-main"><span>Program Keahlian</span><h3>{{ $j->nama_jurusan }}</h3><p>Kode: <strong>{{ $j->kode_jurusan }}</strong></p></div>
                            <div class="major-stats"><div><strong>{{ $j->jumlah_kelas }}</strong><span>Kelas</span></div><div><strong>{{ $j->jumlah_siswa }}</strong><span>Siswa Aktif</span></div></div>
                            <footer><a href="/dashboard/admin/jurusan/detail/{{ $j->id }}" class="card-btn detail">Detail</a><a href="/dashboard/admin/jurusan/edit/{{ $j->id }}" class="card-btn edit">Edit</a><a href="/dashboard/admin/jurusan/delete/{{ $j->id }}" class="card-btn delete" data-confirm="Data akan dihapus dari daftar utama.">Hapus</a></footer>
                        </article>
                    @empty
                        <div class="empty-state"><strong>Jurusan tidak ditemukan</strong><span>Coba ubah pencarian atau filter yang digunakan.</span></div>
                    @endforelse
                </div>
            </section>
        </div>
    </main>
</body></html>
