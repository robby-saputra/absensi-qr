<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Wali Kelas</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-wali_kelas-edit.css') }}">
</head>
<body>
@include('layouts.sidebar_admin')
<main id="content" class="content"><div class="edit-page">
    <section class="edit-hero">
        <div><span>Administrasi Akademik</span><h1>Edit Wali Kelas</h1><p>Perbarui guru penanggung jawab untuk {{ $kelas->nama_kelas }}.</p></div>
        <a href="/dashboard/admin/wali-kelas" class="btn back">Kembali ke Daftar</a>
    </section>
    @include('layouts.alerts')

    <div class="edit-layout">
        <aside class="class-card">
            <div class="class-mark">{{ strtoupper(substr($kelas->nama_kelas, 0, 3)) }}</div>
            <span class="eyebrow">Kelas yang Dikelola</span><h2>{{ $kelas->nama_kelas }}</h2>
            <div class="info-row"><span>Program Keahlian</span><strong>{{ $kelas->kode_jurusan ?? '-' }} · {{ $kelas->nama_jurusan ?? 'Belum ditentukan' }}</strong></div>
            <div class="info-row"><span>Wali Saat Ini</span><strong>{{ $kelas->nama_wali ?? 'Belum ditentukan' }}</strong></div>
            <div class="info-row"><span>Siswa Aktif</span><strong>{{ $kelas->jumlah_siswa }} siswa</strong></div>
        </aside>

        <section class="form-card">
            <header><span>Form Perubahan</span><h2>Pilih Wali Kelas Baru</h2><p>Satu guru hanya dapat menjadi wali untuk satu kelas.</p></header>
            <form method="POST" action="/dashboard/admin/wali-kelas/update/{{ $kelas->id }}">
                @csrf
                <label><span>Nama Kelas</span><input type="text" value="{{ $kelas->nama_kelas }}" readonly><small>Kelas tidak dapat diubah dari halaman ini.</small></label>
                <label><span>Guru Wali Kelas <b>*</b></span><select name="wali_kelas_id" required><option value="">Pilih guru aktif</option>@foreach($guru as $g)<option value="{{ $g->id }}" {{ (string)old('wali_kelas_id', $kelas->wali_kelas_id) === (string)$g->id ? 'selected' : '' }}>{{ $g->nama }} ({{ $g->username }})</option>@endforeach</select><small>Pilih guru yang akan bertanggung jawab atas kelas ini.</small></label>
                <div class="notice"><strong>Perlu diperhatikan</strong><span>Perubahan langsung memindahkan hak akses wali kelas ke guru yang dipilih.</span></div>
                <div class="form-actions"><a href="/dashboard/admin/wali-kelas" class="btn cancel">Batal</a><button type="submit" class="btn save">Simpan Perubahan</button></div>
            </form>
        </section>
    </div>
</div></main>
</body></html>
