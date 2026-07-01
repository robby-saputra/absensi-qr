{{-- File ini menampilkan form absensi harian admin untuk mencatat atau mengubah data kehadiran siswa. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mode === 'edit' ? 'Edit' : 'Tambah' }} Absensi Harian</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-absensi_admin-form.css') }}">
</head>
<body>
@include('layouts.sidebar_admin')
<main id="content" class="content"><div class="attendance-form-page">
    <section class="form-hero">
        <div><span>Administrasi Kehadiran</span><h1>{{ $mode === 'edit' ? 'Edit' : 'Tambah' }} Absensi Harian</h1><p>Catat kehadiran masuk dan pulang siswa berdasarkan laporan guru piket.</p></div>
        <a href="/dashboard/admin/absensi/rekap" class="btn hero-back">Kembali ke Rekap</a>
    </section>
    @include('layouts.alerts')

    <div class="form-layout">
        <aside class="guide-card">
            <div class="guide-icon">AH</div><span class="eyebrow">Panduan Pengisian</span><h2>Absensi Harian Sekolah</h2>
            <p>Form ini digunakan untuk koreksi atau input manual data yang normalnya dicatat oleh guru piket.</p>
            <div class="guide-item"><span>1</span><div><strong>Pilih siswa dan periode</strong><small>Pastikan siswa, tanggal, dan tahun ajaran sesuai.</small></div></div>
            <div class="guide-item"><span>2</span><div><strong>Isi absensi masuk</strong><small>Jam masuk wajib disertai status Hadir atau Telat.</small></div></div>
            <div class="guide-item"><span>3</span><div><strong>Isi absensi pulang</strong><small>Biarkan kosong jika siswa belum melakukan absen pulang.</small></div></div>
            <div class="warning-note"><strong>Catatan penting</strong><span>Jangan memilih status Pulang bila jam pulang belum tercatat.</span></div>
        </aside>

        <form method="POST" action="{{ $mode === 'edit' ? '/dashboard/admin/absensi/update/'.$absensi->id : '/dashboard/admin/absensi/store' }}" class="attendance-form">
            @csrf
            <section class="form-section">
                <header><div class="section-number">01</div><div><span>Identitas Absensi</span><h2>Data Siswa dan Periode</h2><p>Tentukan pemilik dan tanggal catatan absensi.</p></div></header>
                <div class="field-grid">
                    <label class="field wide"><span>Siswa <b>*</b></span><select name="id_siswa" required><option value="">Pilih siswa aktif</option>@foreach($siswa as $s)<option value="{{ $s->id }}" {{ (string)old('id_siswa', $absensi->id_siswa ?? '') === (string)$s->id ? 'selected' : '' }}>{{ $s->nama }}{{ $s->nis ? ' · NIS '.$s->nis : '' }}</option>@endforeach</select><small>Pilih satu siswa yang akan dicatat kehadirannya.</small></label>
                    <label class="field"><span>Tanggal <b>*</b></span><input type="date" name="tanggal" value="{{ old('tanggal', $absensi->tanggal ?? now()->toDateString()) }}" required><small>Tanggal pelaksanaan absensi harian.</small></label>
                    <label class="field"><span>Tahun Ajaran</span><select name="tahun_ajaran_id"><option value="">Gunakan tahun ajaran aktif</option>@foreach($tahunAjaran as $ta)<option value="{{ $ta->id }}" {{ (string)old('tahun_ajaran_id', $tahunAjaranId) === (string)$ta->id ? 'selected' : '' }}>{{ $ta->nama }} · {{ ucfirst($ta->semester) }}{{ $ta->aktif ? ' (Aktif)' : '' }}</option>@endforeach</select><small>Dipakai untuk pemisahan laporan semester.</small></label>
                </div>
            </section>

            <section class="form-section entry">
                <header><div class="section-number green">02</div><div><span>Kehadiran Awal</span><h2>Absensi Masuk</h2><p>Isi jam dan kondisi saat siswa datang ke sekolah.</p></div></header>
                <div class="field-grid two">
                    <label class="field"><span>Jam Masuk</span><input type="time" name="jam_masuk" value="{{ old('jam_masuk', $absensi->jam_masuk ?? '') }}"><small>Kosongkan untuk Izin, Sakit, atau Alfa.</small></label>
                    <label class="field"><span>Status Masuk</span><select name="status_masuk"><option value="">Belum tercatat</option>@foreach(['hadir'=>'Hadir','telat'=>'Terlambat','izin'=>'Izin','sakit'=>'Sakit','alfa'=>'Alfa'] as $value=>$label)<option value="{{ $value }}" {{ old('status_masuk', $absensi->status_masuk ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select><small>Status utama kehadiran siswa pada hari tersebut.</small></label>
                </div>
            </section>

            <section class="form-section exit">
                <header><div class="section-number orange">03</div><div><span>Kehadiran Akhir</span><h2>Absensi Pulang</h2><p>Isi hanya setelah siswa melakukan absensi pulang.</p></div></header>
                <div class="field-grid two">
                    <label class="field"><span>Jam Pulang</span><input type="time" name="jam_pulang" value="{{ old('jam_pulang', $absensi->jam_pulang ?? '') }}"><small>Biarkan kosong jika belum absen pulang.</small></label>
                    <label class="field"><span>Status Pulang</span><select name="status_pulang"><option value="">Belum tercatat</option>@foreach(['pulang'=>'Pulang','pulang_cepat'=>'Pulang Cepat','izin'=>'Izin','sakit'=>'Sakit','alfa'=>'Alfa'] as $value=>$label)<option value="{{ $value }}" {{ old('status_pulang', $absensi->status_pulang ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select><small>Harus sesuai dengan data guru piket.</small></label>
                </div>
            </section>

            <footer class="form-actions"><a href="/dashboard/admin/absensi/rekap" class="btn cancel">Batal</a><button type="submit" class="btn save">{{ $mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Absensi' }}</button></footer>
        </form>
    </div>
</div></main>
</body></html>
