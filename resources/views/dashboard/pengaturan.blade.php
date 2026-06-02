<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Pengaturan Sistem</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/admin-polish.css') }}?v=20260602-polish">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content admin-polish">
        <div class="polish-hero">
            <div>
                <span class="polish-kicker">Konfigurasi</span>
                <h1>Pengaturan Sistem</h1>
                <p>Atur jam absensi, QR, status default, dan identitas sekolah.</p>
            </div>
            <a href="/dashboard/admin" class="polish-btn secondary">Kembali</a>
        </div>

        <div class="setting-grid">
            <section class="polish-panel">
                <div class="polish-panel-head">
                    <div>
                        <h2>Identitas Aktif</h2>
                        <p>Nama dan logo yang tampil di sistem.</p>
                    </div>
                </div>
                <div class="brand-card">
                    <img src="{{ asset($settings['logo_sekolah']) }}" alt="Logo sekolah">
                    <div>
                        <strong>{{ $settings['nama_sekolah'] }}</strong>
                        <span>Logo dan nama ini dipakai sebagai identitas sistem.</span>
                    </div>
                </div>
            </section>
            <section class="polish-panel">
                <div class="polish-panel-head">
                    <div>
                        <h2>Aturan Aktif</h2>
                        <p>Parameter absensi yang sedang berlaku.</p>
                    </div>
                </div>
                <div class="metric-list">
                    <div class="metric-line"><span>Jam masuk</span><strong>{{ $settings['jam_masuk'] }}</strong></div>
                    <div class="metric-line"><span>Batas telat</span><strong>{{ $settings['batas_telat'] }}</strong>
                    </div>
                    <div class="metric-line"><span>Jam pulang</span><strong>{{ $settings['jam_pulang'] }}</strong></div>
                    <div class="metric-line"><span>QR aktif</span><strong>{{ $settings['masa_aktif_qr'] }}
                            menit</strong></div>
                </div>
            </section>
        </div>

        <section class="polish-panel">
            <div class="polish-panel-head">
                <div>
                    <h2>Ubah Pengaturan</h2>
                    <p>Simpan perubahan agar berlaku ke web dan aplikasi mobile.</p>
                </div>
            </div>
            <form method="POST" action="/dashboard/admin/pengaturan" enctype="multipart/form-data"
                class="setting-form">
                @csrf

                <label>
                    Nama Sekolah
                    <input type="text" name="nama_sekolah"
                        value="{{ old('nama_sekolah', $settings['nama_sekolah']) }}" required>
                </label>

                <label>
                    Logo Sekolah
                    <input type="file" name="logo_sekolah" accept="image/png,image/jpeg,image/jpg,image/webp">
                </label>

                <label>
                    Jam Masuk Sekolah
                    <input type="time" name="jam_masuk"
                        value="{{ old('jam_masuk', substr($settings['jam_masuk'], 0, 5)) }}" required>
                </label>

                <label>
                    Batas Telat
                    <input type="time" name="batas_telat"
                        value="{{ old('batas_telat', substr($settings['batas_telat'], 0, 5)) }}" required>
                </label>

                <label>
                    Jam Pulang
                    <input type="time" name="jam_pulang"
                        value="{{ old('jam_pulang', substr($settings['jam_pulang'], 0, 5)) }}" required>
                </label>

                <label>
                    Masa Aktif QR (menit)
                    <input type="number" name="masa_aktif_qr" min="1" max="240"
                        value="{{ old('masa_aktif_qr', $settings['masa_aktif_qr']) }}" required>
                </label>

                <label>
                    Status Default Tidak Hadir
                    <select name="status_default_alfa" required>
                        @foreach (['alfa' => 'Alfa', 'alpa' => 'Alpa'] as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('status_default_alfa', $settings['status_default_alfa']) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <button class="polish-btn full" type="submit">Simpan Pengaturan</button>
            </form>
        </section>
    </main>
</body>

</html>
