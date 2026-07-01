{{-- File ini menampilkan form edit kelas agar admin dapat memperbarui nama kelas, jurusan, atau data wali kelas. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kelas {{ $kelas->nama_kelas }}</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-kelas-edit.css') }}">
    <style>.edit-hero h1 { color: #fff !important; }</style>
</head>
<body>
    @include('layouts.sidebar_admin')
    <main id="content" class="content"><div class="edit-class-page">
        <section class="edit-hero">
            <div><span>Administrasi Kelas</span><h1>Edit Kelas {{ $kelas->nama_kelas }}</h1><p>Perbarui identitas kelas, program keahlian, dan wali kelas.</p></div>
            <a href="/dashboard/admin/kelas" class="hero-back">Kembali ke Daftar</a>
        </section>

        @if(session('error'))<div class="alert error">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="alert error"><strong>Data belum dapat disimpan</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <div class="edit-layout">
            <aside class="class-overview">
                <div class="class-avatar">{{ strtoupper(substr($kelas->nama_kelas,0,3)) }}</div>
                <span>Kelas saat ini</span><h2>{{ $kelas->nama_kelas }}</h2>
                <div class="overview-list"><div><span>Jurusan</span><strong>{{ $jurusanAktif->kode_jurusan ?? '-' }}</strong></div><div><span>Wali Kelas</span><strong>{{ $waliAktif->nama ?? 'Belum ditentukan' }}</strong></div><div><span>Siswa Aktif</span><strong>{{ $jumlahSiswa }} siswa</strong></div></div>
                <p>Perubahan jurusan tidak memindahkan siswa. Pastikan kelas tetap sesuai dengan program keahlian siswa.</p>
            </aside>

            <section class="edit-form-card">
                <header><span>Formulir Perubahan</span><h2>Informasi Kelas</h2><p>Kolom bertanda wajib harus diisi sebelum menyimpan.</p></header>
                <form method="POST" action="/dashboard/admin/kelas/update/{{ $kelas->id }}">@csrf
                    <div class="field full"><label for="nama_kelas">Nama Kelas <b>*</b></label><input id="nama_kelas" type="text" name="nama_kelas" value="{{ old('nama_kelas',$kelas->nama_kelas) }}" required placeholder="Contoh: XI TKJ 1"><small>Gunakan nama yang mudah dikenali pada jadwal dan laporan.</small></div>
                    <div class="field"><label for="jurusan_id">Program Keahlian <b>*</b></label><select id="jurusan_id" name="jurusan_id" required>@foreach($jurusan as $j)<option value="{{ $j->id }}" {{ old('jurusan_id',$kelas->jurusan_id)==$j->id?'selected':'' }}>{{ $j->kode_jurusan }} · {{ $j->nama_jurusan }}</option>@endforeach</select><small>Menentukan jurusan utama kelas ini.</small></div>
                    <div class="field"><label for="wali_kelas_id">Wali Kelas</label><select id="wali_kelas_id" name="wali_kelas_id"><option value="">Belum ada wali kelas</option>@foreach($guru as $g)<option value="{{ $g->id }}" {{ old('wali_kelas_id',$kelas->wali_kelas_id)==$g->id?'selected':'' }}>{{ $g->nama }}</option>@endforeach</select><small>Satu guru hanya dapat menjadi wali pada satu kelas.</small></div>
                    <div class="form-note"><strong>Periksa sebelum menyimpan</strong><span>Nama kelas akan digunakan pada jadwal, absensi, rekap, dan laporan resmi.</span></div>
                    <footer><a class="btn cancel" href="/dashboard/admin/kelas">Batal</a><button class="btn save" type="submit">Simpan Perubahan</button></footer>
                </form>
            </section>
        </div>
    </div></main>
</body></html>
