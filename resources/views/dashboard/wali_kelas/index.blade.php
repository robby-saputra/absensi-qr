{{-- File ini menampilkan daftar wali kelas untuk mengelola penanggung jawab kelas dan distribusi siswa. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Wali Kelas</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-wali_kelas-index.css') }}">
</head>
<body>
@include('layouts.sidebar_admin')
<main id="content" class="content"><div class="wali-page">
    @include('layouts.alerts')
    <section class="wali-hero">
        <div><span>Administrasi Akademik</span><h1>Kelola Wali Kelas</h1><p>Atur penanggung jawab kelas dan pantau distribusi siswa dengan lebih mudah.</p></div>
        <div class="hero-actions"><a href="/dashboard/admin" class="btn ghost">Kembali</a><a href="/dashboard/admin/rekap/wali-kelas-pdf" target="_blank" class="btn soft">PDF Resmi</a><a href="/dashboard/admin/wali-kelas/create" class="btn primary">+ Tambah Wali Kelas</a></div>
    </section>

    <section class="summary-grid">
        <article><span>Kelas Memiliki Wali</span><strong>{{ $ringkasan['wali'] }}</strong><small>penugasan aktif</small></article>
        <article><span>Belum Memiliki Wali</span><strong>{{ $ringkasan['belum'] }}</strong><small>kelas perlu dilengkapi</small></article>
        <article><span>Siswa Terpantau</span><strong>{{ $ringkasan['siswa'] }}</strong><small>siswa aktif</small></article>
        <article><span>Guru Aktif</span><strong>{{ $ringkasan['guru'] }}</strong><small>tersedia di sekolah</small></article>
    </section>

    <section class="wali-panel">
        <header class="panel-head"><div><span>Daftar Penugasan</span><h2>Wali Kelas Aktif</h2><p>{{ $wali->count() }} hasil ditemukan</p></div></header>
        <form method="GET" class="wali-filter">
            <label class="search-field"><span>Pencarian</span><input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari kelas, guru, username, atau jurusan..."></label>
            <label><span>Jurusan</span><select name="jurusan_id"><option value="">Semua jurusan</option>@foreach($jurusan as $jrs)<option value="{{ $jrs->id }}" {{ (string)($filters['jurusan_id'] ?? '') === (string)$jrs->id ? 'selected' : '' }}>{{ $jrs->kode_jurusan }} · {{ $jrs->nama_jurusan }}</option>@endforeach</select></label>
            <label><span>Data Siswa</span><select name="siswa_status"><option value="">Semua kelas</option><option value="terisi" {{ ($filters['siswa_status'] ?? '') === 'terisi' ? 'selected' : '' }}>Memiliki siswa</option><option value="kosong" {{ ($filters['siswa_status'] ?? '') === 'kosong' ? 'selected' : '' }}>Kelas kosong</option></select></label>
            <label><span>Urutkan</span><select name="sort"><option value="kelas" {{ ($filters['sort'] ?? 'kelas') === 'kelas' ? 'selected' : '' }}>Nama kelas</option><option value="wali" {{ ($filters['sort'] ?? '') === 'wali' ? 'selected' : '' }}>Nama wali kelas</option><option value="siswa_terbanyak" {{ ($filters['sort'] ?? '') === 'siswa_terbanyak' ? 'selected' : '' }}>Siswa terbanyak</option><option value="siswa_tersedikit" {{ ($filters['sort'] ?? '') === 'siswa_tersedikit' ? 'selected' : '' }}>Siswa tersedikit</option></select></label>
            <div class="filter-actions"><button class="btn apply" type="submit">Terapkan</button><a class="btn reset" href="/dashboard/admin/wali-kelas">Reset</a></div>
        </form>

        <div class="table-wrap"><table class="wali-table"><thead><tr><th>Kelas</th><th>Program Keahlian</th><th>Wali Kelas</th><th>Siswa Aktif</th><th class="action-head">Aksi</th></tr></thead><tbody>
        @forelse($wali as $w)
            @php $inisial = collect(explode(' ', trim($w->nama ?? '')))->filter()->take(2)->map(fn($kata) => strtoupper(substr($kata, 0, 1)))->implode(''); @endphp
            <tr>
                <td data-label="Kelas"><div class="class-name"><span>{{ strtoupper(substr($w->nama_kelas, 0, 3)) }}</span><div><strong>{{ $w->nama_kelas }}</strong><small>ID Kelas #{{ $w->id }}</small></div></div></td>
                <td data-label="Jurusan"><span class="major-badge">{{ $w->kode_jurusan ?? '-' }}</span><small class="major-name">{{ $w->nama_jurusan ?? 'Belum ditentukan' }}</small></td>
                <td data-label="Wali Kelas"><div class="teacher"><span>{{ $inisial ?: '?' }}</span><div><strong>{{ $w->nama ?? 'Belum ditentukan' }}</strong><small>{{ '@'.($w->username ?? '-') }}</small></div></div></td>
                <td data-label="Siswa"><strong class="student-count">{{ $w->jumlah_siswa }}</strong><small>siswa aktif</small></td>
                <td data-label="Aksi"><div class="row-actions"><a href="/dashboard/admin/wali-kelas/edit/{{ $w->id }}" class="action-btn edit">Edit</a><a href="/dashboard/admin/wali-kelas/delete/{{ $w->id }}" class="action-btn delete" data-confirm="Hapus penugasan {{ $w->nama ?? 'wali kelas' }} dari kelas {{ $w->nama_kelas }}? Data kelas dan siswa tidak akan ikut terhapus.">Hapus</a></div></td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty-state"><strong>Wali kelas tidak ditemukan</strong><span>Coba ubah pencarian atau filter yang digunakan.</span></div></td></tr>
        @endforelse
        </tbody></table></div>
    </section>
</div></main>
</body></html>
