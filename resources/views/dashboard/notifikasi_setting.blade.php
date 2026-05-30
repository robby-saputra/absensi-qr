<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Pengaturan Notifikasi</title><link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}"></head><body>
@include('layouts.sidebar_admin')
<main id="content" class="content"><div class="rekap-head"><div><h1>Pengaturan Notifikasi</h1><p>Atur notifikasi keamanan dan operasional absensi.</p></div><a class="btn back" href="/dashboard/admin">Kembali</a></div>
<form method="POST" class="rekap-filter">@csrf
<label><input type="checkbox" name="notif_login_mencurigakan" value="1" {{ ($settings['notif_login_mencurigakan'] ?? '1') === '1' ? 'checked' : '' }}> Login mencurigakan aktif</label>
<label>Threshold gagal login <input type="number" min="1" max="20" name="notif_login_threshold" value="{{ $settings['notif_login_threshold'] ?? 3 }}"></label>
<label><input type="checkbox" name="notif_pengajuan_izin_guru" value="1" {{ ($settings['notif_pengajuan_izin_guru'] ?? '1') === '1' ? 'checked' : '' }}> Pengajuan izin ke guru mapel/piket</label>
<label><input type="checkbox" name="notif_belum_absen_pulang" value="1" {{ ($settings['notif_belum_absen_pulang'] ?? '1') === '1' ? 'checked' : '' }}> Pengingat belum absen pulang</label>
<button class="btn" type="submit">Simpan</button></form>
</main></body></html>
