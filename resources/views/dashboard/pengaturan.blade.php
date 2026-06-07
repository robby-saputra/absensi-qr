<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Pengaturan Absensi</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/admin-polish.css') }}?v=20260602-polish">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content admin-polish">
        <div class="polish-hero">
            <div>
                <span class="polish-kicker">Konfigurasi Absensi</span>
                <h1>Pengaturan Absensi</h1>
                <p>Atur periode, jam scan QR, lokasi sekolah, dan identitas aplikasi.</p>
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
                    <div class="metric-line">
                        <span>Tahun Ajaran Aktif</span>
                        <strong>{{ $tahunAjaranAktif->nama ?? 'Belum diatur' }}</strong>
                    </div>
                    <div class="metric-line">
                        <span>Semester Aktif</span>
                        <strong>{{ $tahunAjaranAktif ? ucfirst($tahunAjaranAktif->semester) : 'Belum diatur' }}</strong>
                    </div>
                    <div class="metric-line"><span>Jam masuk</span><strong>{{ $settings['jam_masuk'] }}</strong></div>
                    <div class="metric-line"><span>Batas telat</span><strong>{{ $settings['batas_telat'] }}</strong>
                    </div>
                    <div class="metric-line"><span>Jam pulang</span><strong>{{ $settings['jam_pulang'] }}</strong></div>
                    <div class="metric-line"><span>Jam kunci</span><strong>{{ $settings['jam_kunci_absensi'] }}</strong>
                    </div>
                    <div class="metric-line"><span>QR aktif</span><strong>{{ $settings['masa_aktif_qr'] }}
                            menit</strong></div>
                    <div class="metric-line"><span>Radius lokasi</span><strong>{{ $settings['radius_absensi'] }}
                            meter</strong></div>
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
                    Tahun Ajaran Aktif
                    <select name="tahun_ajaran_id">
                        <option value="">Biarkan aktif saat ini</option>
                        @foreach ($tahunAjaran as $ta)
                            <option value="{{ $ta->id }}"
                                {{ old('tahun_ajaran_id', $tahunAjaranAktif->id ?? '') == $ta->id ? 'selected' : '' }}>
                                {{ $ta->nama }} - {{ ucfirst($ta->semester) }}
                            </option>
                        @endforeach
                    </select>
                    <small>Tahun ajaran dipakai sebagai periode absensi dan laporan.</small>
                    <a href="/dashboard/admin/tahun-ajaran" class="polish-btn secondary">Kelola Tahun Ajaran</a>
                </label>

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
                    Jam Kunci Absensi
                    <input type="time" name="jam_kunci_absensi"
                        value="{{ old('jam_kunci_absensi', substr($settings['jam_kunci_absensi'], 0, 5)) }}"
                        required>
                    <small>Setelah melewati jam ini, guru piket dan guru mata pelajaran tidak dapat mengubah data
                        absensi. Koreksi hanya dapat dilakukan oleh admin.</small>
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

                <label>
                    Latitude Sekolah
                    <input type="number" name="latitude_sekolah" step="0.000001" min="-90" max="90"
                        value="{{ old('latitude_sekolah', $settings['latitude_sekolah']) }}" required>
                </label>

                <label>
                    Longitude Sekolah
                    <input type="number" name="longitude_sekolah" step="0.000001" min="-180" max="180"
                        value="{{ old('longitude_sekolah', $settings['longitude_sekolah']) }}" required>
                </label>

                <label>
                    Radius Absensi (meter)
                    <input type="number" name="radius_absensi" min="1" max="5000"
                        value="{{ old('radius_absensi', $settings['radius_absensi']) }}" required>
                </label>

                <button class="polish-btn full" type="submit">Simpan Pengaturan</button>
            </form>
        </section>
    </main>
</body>

</html>
