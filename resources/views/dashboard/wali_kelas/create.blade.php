{{-- File ini menampilkan form tambah wali kelas untuk menentukan guru penanggung jawab pada kelas tertentu. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Wali Kelas</title>
</head>

<body>
    @include('layouts.sidebar_admin')
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-wali_kelas-create.css') }}?v={{ filemtime(public_path('css/pages/dashboard-wali_kelas-create.css')) }}">

    <main id="content" class="content">
        <div class="wali-create-page">
            <section class="create-hero">
                <div>
                    <span>Administrasi Akademik</span>
                    <h1>Tambah Wali Kelas</h1>
                    <p>Tentukan guru penanggung jawab kelas agar pemantauan siswa dan absensi berjalan lebih tertata.</p>
                </div>
                <a href="/dashboard/admin/wali-kelas" class="hero-back">Kembali</a>
            </section>

            @if (session('error'))
                <div class="create-alert error">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="create-alert error">
                    <strong>Data belum bisa disimpan.</strong>
                    <ul class="form-errors">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="create-layout">
                <aside class="guide-card">
                    <div class="guide-icon">WK</div>
                    <span class="eyebrow">Panduan Pengisian</span>
                    <h2>Penugasan Wali</h2>
                    <p>Pilih satu guru dan satu kelas. Sistem akan menyimpan hubungan keduanya sebagai wali kelas aktif.</p>

                    <div class="guide-list">
                        <div>
                            <span>01</span>
                            <div>
                                <strong>Pilih guru aktif</strong>
                                <small>Guru yang dipilih akan memiliki akses pemantauan kelas.</small>
                            </div>
                        </div>
                        <div>
                            <span>02</span>
                            <div>
                                <strong>Pilih kelas tujuan</strong>
                                <small>Kelas ini akan terhubung dengan wali yang dipilih.</small>
                            </div>
                        </div>
                        <div>
                            <span>03</span>
                            <div>
                                <strong>Simpan penugasan</strong>
                                <small>Pastikan data sudah benar sebelum menekan tombol simpan.</small>
                            </div>
                        </div>
                    </div>
                </aside>

                <section class="assignment-card">
                    <header>
                        <div>
                            <span>Form Penugasan</span>
                            <h2>Data Wali Kelas</h2>
                            <p>Lengkapi data guru dan kelas yang akan dipasangkan.</p>
                        </div>
                        <div class="mini-stats">
                            <div>
                                <strong>{{ count($guru) }}</strong>
                                <span>Guru</span>
                            </div>
                            <div>
                                <strong>{{ count($kelas) }}</strong>
                                <span>Kelas</span>
                            </div>
                        </div>
                    </header>

                    <form method="POST" action="/dashboard/admin/wali-kelas/store">
                        @csrf

                        <label class="field">
                            <span>Guru <b>*</b></span>
                            <select name="guru_id" required>
                                <option value="">Pilih guru yang menjadi wali kelas</option>
                                @foreach ($guru as $g)
                                    <option value="{{ $g->id }}" {{ (string) old('guru_id') === (string) $g->id ? 'selected' : '' }}>
                                        {{ $g->nama }}
                                    </option>
                                @endforeach
                            </select>
                            <small>Setiap guru hanya dapat ditugaskan sebagai wali untuk satu kelas.</small>
                        </label>

                        <label class="field">
                            <span>Kelas <b>*</b></span>
                            <select name="kelas_id" required>
                                <option value="">Pilih kelas yang akan memiliki wali</option>
                                @foreach ($kelas as $k)
                                    <option value="{{ $k->id }}" {{ (string) old('kelas_id') === (string) $k->id ? 'selected' : '' }}>
                                        {{ $k->nama_kelas }}
                                    </option>
                                @endforeach
                            </select>
                            <small>Pilih kelas yang belum atau perlu diperbarui penanggung jawabnya.</small>
                        </label>

                        <div class="form-note">
                            <strong>Catatan</strong>
                            <span>Data wali kelas akan digunakan pada dashboard wali kelas, pemantauan siswa, dan laporan akademik.</span>
                        </div>

                        <footer class="form-actions">
                            <a href="/dashboard/admin/wali-kelas" class="btn cancel">Kembali</a>
                            <button type="submit" class="btn save">Simpan Wali Kelas</button>
                        </footer>
                    </form>
                </section>
            </div>
        </div>
    </main>
</body>

</html>
