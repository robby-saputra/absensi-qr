<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Pengaturan Notifikasi</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-notifikasi.css') }}?v=20260602-notif">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content notif-page">
        <div class="page-head panel-headline">
            <div>
                <span class="eyebrow">Preferensi Admin</span>
                <h1>Pengaturan Notifikasi</h1>
                <p>Atur notifikasi yang masuk ke lonceng admin dan peringatan operasional sistem.</p>
            </div>
            <a class="btn-back" href="/dashboard/admin">Kembali</a>
        </div>

        <form method="POST" class="settings-panel">
            @csrf
            <div class="setting-card">
                <div>
                    <strong>Absensi masuk siswa</strong>
                    <span>Kirim notifikasi ke lonceng admin saat siswa berhasil scan masuk.</span>
                </div>
                <label class="switch">
                    <input type="checkbox" name="notif_absen_masuk_admin" value="1"
                        {{ ($settings['notif_absen_masuk_admin'] ?? '1') === '1' ? 'checked' : '' }}>
                    <span></span>
                </label>
            </div>

            <div class="setting-card">
                <div>
                    <strong>Login mencurigakan</strong>
                    <span>Beritahu admin saat ada percobaan login gagal berulang.</span>
                </div>
                <label class="switch">
                    <input type="checkbox" name="notif_login_mencurigakan" value="1"
                        {{ ($settings['notif_login_mencurigakan'] ?? '1') === '1' ? 'checked' : '' }}>
                    <span></span>
                </label>
            </div>

            <div class="setting-card setting-card-input">
                <div>
                    <strong>Threshold gagal login</strong>
                    <span>Jumlah percobaan gagal sebelum dikategorikan mencurigakan.</span>
                </div>
                <input type="number" min="1" max="20" name="notif_login_threshold"
                    value="{{ $settings['notif_login_threshold'] ?? 3 }}">
            </div>

            <div class="setting-card">
                <div>
                    <strong>Pengajuan izin ke guru</strong>
                    <span>Kirim pengingat pengajuan izin ke guru mapel/piket terkait.</span>
                </div>
                <label class="switch">
                    <input type="checkbox" name="notif_pengajuan_izin_guru" value="1"
                        {{ ($settings['notif_pengajuan_izin_guru'] ?? '1') === '1' ? 'checked' : '' }}>
                    <span></span>
                </label>
            </div>

            <div class="setting-card">
                <div>
                    <strong>Belum absen pulang</strong>
                    <span>Aktifkan pengingat untuk siswa yang belum absen pulang.</span>
                </div>
                <label class="switch">
                    <input type="checkbox" name="notif_belum_absen_pulang" value="1"
                        {{ ($settings['notif_belum_absen_pulang'] ?? '1') === '1' ? 'checked' : '' }}>
                    <span></span>
                </label>
            </div>

            <div class="settings-actions">
                <button class="btn-primary" type="submit">Simpan Pengaturan</button>
            </div>
        </form>
    </main>
</body>

</html>
