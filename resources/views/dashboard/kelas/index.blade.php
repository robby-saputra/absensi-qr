<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kelas</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-kelas-index.css') }}">
</head>
<body>
    @include('layouts.sidebar_admin')
    <main id="content" class="content"><div class="class-page">
        @include('layouts.alerts')
        <section class="class-hero">
            <div><span>Administrasi Akademik</span><h1>Kelola Kelas</h1><p>Kelola kelas, program keahlian, wali kelas, dan distribusi siswa.</p></div>
            <div class="hero-actions"><a href="/dashboard/admin" class="btn ghost">Kembali</a><a href="/dashboard/admin/pdf/kelas" target="_blank" class="btn soft">PDF Resmi</a><a href="/dashboard/admin/kelas/create" class="btn primary">＋ Tambah Kelas</a></div>
        </section>

        <section class="summary-grid">
            <div><span>Total Kelas</span><strong>{{ $ringkasan['kelas'] }}</strong></div>
            <div><span>Siswa Terdaftar</span><strong>{{ $ringkasan['siswa'] }}</strong></div>
            <div><span>Tanpa Wali Kelas</span><strong>{{ $ringkasan['tanpa_wali'] }}</strong></div>
            <div><span>Kelas Kosong</span><strong>{{ $ringkasan['kosong'] }}</strong></div>
        </section>

        <section class="class-panel">
            <div class="panel-head"><div><span>Daftar Kelas</span><h2>Data Kelas Sekolah</h2><p>{{ $kelas->count() }} hasil ditemukan</p></div></div>
            <form method="GET" class="kelas-filter">
                <label class="search-field"><span>Pencarian</span><input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama kelas, jurusan, atau wali kelas..."></label>
                <label><span>Jurusan</span><select name="jurusan_id"><option value="">Semua jurusan</option>@foreach($jurusan as $jrs)<option value="{{ $jrs->id }}" {{ (string)($filters['jurusan_id']??'')===(string)$jrs->id?'selected':'' }}>{{ $jrs->kode_jurusan }} · {{ $jrs->nama_jurusan }}</option>@endforeach</select></label>
                <label><span>Wali Kelas</span><select name="wali_status"><option value="">Semua status</option><option value="ada" {{ ($filters['wali_status']??'')==='ada'?'selected':'' }}>Sudah ada wali</option><option value="belum" {{ ($filters['wali_status']??'')==='belum'?'selected':'' }}>Belum ada wali</option></select></label>
                <label><span>Data Siswa</span><select name="siswa_status"><option value="">Semua kelas</option><option value="terisi" {{ ($filters['siswa_status']??'')==='terisi'?'selected':'' }}>Memiliki siswa</option><option value="kosong" {{ ($filters['siswa_status']??'')==='kosong'?'selected':'' }}>Kelas kosong</option></select></label>
                <div class="filter-actions"><button class="btn apply" type="submit">Terapkan</button><a class="btn reset" href="/dashboard/admin/kelas">Reset</a></div>
            </form>

            <div class="table-wrap"><table class="class-table"><thead><tr><th>Kelas</th><th>Program Keahlian</th><th>Wali Kelas</th><th>Siswa Aktif</th><th>Status</th><th class="action-head">Aksi</th></tr></thead><tbody>
                @forelse($kelas as $k)
                    @php $inisial=collect(explode(' ',trim($k->nama_wali??'')))->filter()->take(2)->map(fn($kata)=>strtoupper(substr($kata,0,1)))->implode(''); @endphp
                    <tr>
                        <td data-label="Kelas"><div class="class-name"><span>{{ strtoupper(substr($k->nama_kelas,0,3)) }}</span><div><strong>{{ $k->nama_kelas }}</strong><small>ID Kelas #{{ $k->id }}</small></div></div></td>
                        <td data-label="Jurusan"><span class="major-badge">{{ $k->kode_jurusan??'-' }}</span><small class="major-name">{{ $k->nama_jurusan??'Belum ditentukan' }}</small></td>
                        <td data-label="Wali Kelas"><div class="teacher"><span>{{ $inisial?:'?' }}</span><strong>{{ $k->nama_wali??'Belum ditentukan' }}</strong></div></td>
                        <td data-label="Siswa"><strong class="student-count">{{ $k->jumlah_siswa }}</strong><small>siswa aktif</small></td>
                        <td data-label="Status"><span class="status-badge {{ $k->jumlah_siswa>0?'active':'empty' }}">{{ $k->jumlah_siswa>0?'Kelas aktif':'Belum ada siswa' }}</span></td>
                        <td data-label="Aksi"><div class="row-actions"><a href="/dashboard/admin/kelas/edit/{{ $k->id }}" class="action-btn edit">Edit</a><a href="/dashboard/admin/kelas/delete/{{ $k->id }}" class="action-btn delete" data-confirm="Data akan dihapus dari daftar utama.">Hapus</a></div></td>
                    </tr>
                @empty<tr><td colspan="6"><div class="empty-state"><strong>Kelas tidak ditemukan</strong><span>Coba ubah pencarian atau filter yang digunakan.</span></div></td></tr>@endforelse
            </tbody></table></div>
        </section>
    </div></main>
</body></html>
