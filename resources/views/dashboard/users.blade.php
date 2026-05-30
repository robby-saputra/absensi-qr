<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Siswa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>
<body>
@include('layouts.alerts')
<main class="content" style="margin-left:0">
    <div class="rekap-head">
        <div>
            <h1>Dashboard Siswa</h1>
            <p>{{ $user->nama }} - {{ $kelas->nama_kelas ?? '-' }} {{ $kelas->nama_jurusan ?? '' }}</p>
        </div>
        <a class="btn back" href="/logout">Logout</a>
    </div>

    <div class="rekap-filter">
        <span class="status-pill">Tanggal: {{ $tanggal }}</span>
        <span class="status-pill">Masuk: {{ $absensiHariIni->jam_masuk ?? '-' }} {{ $absensiHariIni->status_masuk ? '('.$absensiHariIni->status_masuk.')' : '' }}</span>
        <span class="status-pill">Pulang: {{ $absensiHariIni->jam_pulang ?? '-' }} {{ $absensiHariIni->status_pulang ? '('.$absensiHariIni->status_pulang.')' : '' }}</span>
    </div>

    <h2>Ajukan Izin/Sakit</h2>
    <form method="POST" action="/dashboard/users/izin/store" enctype="multipart/form-data" class="rekap-filter">
        @csrf
        <select name="jenis" required>
            <option value="izin">Izin</option>
            <option value="sakit">Sakit</option>
        </select>
        <input type="date" name="tanggal_mulai" value="{{ now()->toDateString() }}" required>
        <input type="date" name="tanggal_selesai" value="{{ now()->toDateString() }}" required>
        <input type="file" name="bukti" accept=".jpg,.jpeg,.png,.pdf">
        <textarea name="alasan" rows="3" placeholder="Alasan singkat" style="width:100%"></textarea>
        <button class="btn" type="submit">Kirim Pengajuan</button>
    </form>

    <h2>Riwayat Pengajuan</h2>
    <table>
        <tr><th>Tanggal</th><th>Jenis</th><th>Status</th><th>Catatan</th></tr>
        @forelse($pengajuan as $p)
            <tr><td>{{ $p->tanggal_mulai }} s/d {{ $p->tanggal_selesai }}</td><td>{{ $p->jenis }}</td><td><span class="status-pill">{{ $p->status }}</span></td><td>{{ $p->catatan_review ?? '-' }}</td></tr>
        @empty
            <tr><td colspan="4" class="empty-row">Belum ada pengajuan.</td></tr>
        @endforelse
    </table>

    <h2 style="margin-top:22px">Riwayat Absensi Harian</h2>
    <table>
        <tr><th>Tanggal</th><th>Masuk</th><th>Status Masuk</th><th>Pulang</th><th>Status Pulang</th></tr>
        @forelse($riwayatHarian as $a)
            <tr><td>{{ $a->tanggal }}</td><td>{{ $a->jam_masuk ?? '-' }}</td><td>{{ $a->status_masuk ?? '-' }}</td><td>{{ $a->jam_pulang ?? '-' }}</td><td>{{ $a->status_pulang ?? '-' }}</td></tr>
        @empty
            <tr><td colspan="5" class="empty-row">Belum ada riwayat harian.</td></tr>
        @endforelse
    </table>

    <h2 style="margin-top:22px">Riwayat Absensi Mapel</h2>
    <table>
        <tr><th>Tanggal</th><th>Mapel</th><th>Guru</th><th>Jam Scan</th><th>Status</th></tr>
        @forelse($riwayatMapel as $a)
            <tr><td>{{ $a->tanggal }}</td><td>{{ $a->nama_mapel }}</td><td>{{ $a->nama_guru }}</td><td>{{ $a->jam_scan ?? '-' }}</td><td>{{ $a->status }}</td></tr>
        @empty
            <tr><td colspan="5" class="empty-row">Belum ada riwayat mapel.</td></tr>
        @endforelse
    </table>
</main>
<script src="{{ asset('js/app-ui.js') }}"></script>
</body>
</html>
