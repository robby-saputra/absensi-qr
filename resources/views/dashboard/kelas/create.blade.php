{{-- File ini menampilkan form tambah kelas baru sebagai data master untuk pengelompokan siswa. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kelas</title>
</head>

<body>

    @include('layouts.sidebar_admin')
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-kelas-create.css') }}?v={{ filemtime(public_path('css/pages/dashboard-kelas-create.css')) }}">

    <main id="content" class="content">
        <div class="class-create-page">
            <section class="create-hero">
                <div>
                    <span>Administrasi Kelas</span>
                    <h1>Tambah Kelas</h1>
                    <p>Buat data kelas baru, hubungkan dengan program keahlian, dan tetapkan wali kelas jika sudah tersedia.</p>
                </div>
                <a href="/dashboard/admin/kelas" class="hero-back">Kembali ke Daftar</a>
            </section>

            @if (session('error'))
                <div class="create-alert error">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="create-alert error">
                    <strong>Data kelas belum bisa disimpan.</strong>
                    <ul class="form-errors">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="create-layout">
                <aside class="guide-card">
                    <div class="class-avatar">KL</div>
                    <span class="eyebrow">Panduan Pengisian</span>
                    <h2>Data Kelas Baru</h2>
                    <p>Data kelas menjadi dasar untuk penempatan siswa, jadwal pelajaran, absensi, dan laporan sekolah.</p>

                    <div class="guide-list">
                        <div>
                            <span>01</span>
                            <div>
                                <strong>Nama kelas jelas</strong>
                                <small>Gunakan format yang mudah dikenali, misalnya X TKJ 1.</small>
                            </div>
                        </div>
                        <div>
                            <span>02</span>
                            <div>
                                <strong>Pilih program keahlian</strong>
                                <small>Jurusan menentukan pengelompokan kelas pada data akademik.</small>
                            </div>
                        </div>
                        <div>
                            <span>03</span>
                            <div>
                                <strong>Wali kelas opsional</strong>
                                <small>Bisa dikosongkan jika penanggung jawab kelas belum ditentukan.</small>
                            </div>
                        </div>
                    </div>
                </aside>

                <section class="create-form-card">
                    <header>
                        <span>Formulir Kelas</span>
                        <h2>Informasi Kelas</h2>
                        <p>Kolom bertanda wajib harus diisi sebelum menyimpan.</p>
                    </header>

                    <form method="POST" action="/dashboard/admin/kelas/store">
                        @csrf

                        <div class="field full">
                            <label for="nama_kelas">Nama Kelas <b>*</b></label>
                            <input id="nama_kelas" type="text" name="nama_kelas" value="{{ old('nama_kelas') }}" required placeholder="Contoh: X TKJ 1">
                            <small>Nama kelas akan digunakan pada jadwal, absensi, dan laporan resmi.</small>
                        </div>

                        <div class="field">
                            <label for="jurusan_id">Program Keahlian <b>*</b></label>
                            <select id="jurusan_id" name="jurusan_id" required>
                                <option value="">Pilih jurusan</option>
                                @foreach ($jurusan as $j)
                                    <option value="{{ $j->id }}" {{ (string) old('jurusan_id') === (string) $j->id ? 'selected' : '' }}>
                                        {{ $j->kode_jurusan }} - {{ $j->nama_jurusan }}
                                    </option>
                                @endforeach
                            </select>
                            <small>Menentukan jurusan utama kelas ini.</small>
                        </div>

                        <div class="field">
                            <label for="wali_kelas_id">Wali Kelas</label>
                            <select id="wali_kelas_id" name="wali_kelas_id">
                                <option value="">Belum ada wali kelas</option>
                                @foreach ($guru as $g)
                                    <option value="{{ $g->id }}" {{ (string) old('wali_kelas_id') === (string) $g->id ? 'selected' : '' }}>
                                        {{ $g->nama }}
                                    </option>
                                @endforeach
                            </select>
                            <small>Satu guru hanya dapat menjadi wali pada satu kelas.</small>
                        </div>

                        <div class="form-note">
                            <strong>Periksa sebelum menyimpan</strong>
                            <span>Pastikan kelas, jurusan, dan wali kelas sudah sesuai agar data siswa mudah dikelola.</span>
                        </div>

                        <footer class="form-actions">
                            <a href="/dashboard/admin/kelas" class="btn cancel">Kembali</a>
                            <button type="submit" class="btn save">Simpan Kelas</button>
                        </footer>
                    </form>
                </section>
            </div>
        </div>

    </main>

</body>

</html>
