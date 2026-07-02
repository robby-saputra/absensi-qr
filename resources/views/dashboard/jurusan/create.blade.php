{{-- File ini menampilkan form tambah jurusan baru sebagai data master program keahlian di sekolah. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Jurusan</title>
</head>

<body>

    @include('layouts.sidebar_admin')
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-jurusan-create.css') }}?v={{ filemtime(public_path('css/pages/dashboard-jurusan-create.css')) }}">

    <main id="content" class="content">
        <div class="major-create-page">
            <section class="create-hero">
                <div>
                    <span>Data Akademik</span>
                    <h1>Tambah Jurusan</h1>
                    <p>Tambahkan program keahlian baru agar data kelas, siswa, dan laporan akademik tersusun lebih rapi.</p>
                </div>
                <a href="/dashboard/admin/jurusan" class="hero-back">Kembali</a>
            </section>

            @if ($errors->any())
                <div class="create-alert error">
                    <strong>Data jurusan belum bisa disimpan.</strong>
                    <ul class="form-errors">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="create-layout">
                <aside class="guide-card">
                    <div class="guide-icon">JR</div>
                    <span class="eyebrow">Panduan Pengisian</span>
                    <h2>Program Keahlian</h2>
                    <p>Data jurusan digunakan untuk mengelompokkan kelas dan siswa berdasarkan program keahlian di sekolah.</p>

                    <div class="guide-list">
                        <div>
                            <span>01</span>
                            <div>
                                <strong>Nama jurusan lengkap</strong>
                                <small>Gunakan nama resmi seperti Teknik Komputer dan Jaringan.</small>
                            </div>
                        </div>
                        <div>
                            <span>02</span>
                            <div>
                                <strong>Kode jurusan singkat</strong>
                                <small>Kode seperti TKJ, RPL, atau AKL membantu identifikasi data.</small>
                            </div>
                        </div>
                        <div>
                            <span>03</span>
                            <div>
                                <strong>Simpan data master</strong>
                                <small>Jurusan baru dapat dipakai saat membuat kelas dan laporan.</small>
                            </div>
                        </div>
                    </div>
                </aside>

                <section class="major-form-card">
                    <header>
                        <div>
                            <span>Form Jurusan</span>
                            <h2>Data Program Keahlian</h2>
                            <p>Isi nama dan kode jurusan sesuai data akademik sekolah.</p>
                        </div>
                    </header>

                    <form method="POST" action="/dashboard/admin/jurusan/store">
                        @csrf

                        <label class="field">
                            <span>Nama Jurusan <b>*</b></span>
                            <input type="text" name="nama_jurusan" value="{{ old('nama_jurusan') }}" placeholder="Contoh: Teknik Komputer Jaringan">
                            <small>Nama ini akan tampil pada daftar jurusan, data kelas, dan laporan.</small>
                        </label>

                        <label class="field">
                            <span>Kode Jurusan <b>*</b></span>
                            <input type="text" name="kode_jurusan" value="{{ old('kode_jurusan') }}" placeholder="Contoh: TKJ">
                            <small>Gunakan kode singkat agar mudah dibaca pada tabel dan kartu data.</small>
                        </label>

                        <div class="form-note">
                            <strong>Catatan</strong>
                            <span>Pastikan nama dan kode jurusan tidak duplikat agar data kelas lebih mudah dikelola.</span>
                        </div>

                        <footer class="form-actions">
                            <a href="/dashboard/admin/jurusan" class="btn cancel">Kembali</a>
                            <button type="submit" class="btn save">Simpan Jurusan</button>
                        </footer>
                    </form>
                </section>
            </div>
        </div>

    </main>

</body>

</html>
