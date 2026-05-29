<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Detail Absensi Harian</title>
<link rel="stylesheet" href="{{ asset('css/pages/dashboard-piket.css') }}">
</head>
<body>
@include('layouts.sidebar_piket')
<main id="content" class="content">
    <section class="card">
        <h3>Detail Absensi Harian</h3>
        <p class="muted">Data absensi harian dari guru piket.</p>
        <table>
            <tr><th>Nama</th><td>{{ $siswa->nama }}</td></tr>
            <tr><th>NIS</th><td>{{ $siswa->nis ?? '-' }}</td></tr>
            <tr><th>Kelas</th><td>{{ $kelas->nama_kelas ?? '-' }}</td></tr>
            <tr><th>Tanggal</th><td>{{ $tanggal }}</td></tr>
            <tr><th>Absen Harian Masuk</th><td>{{ $absensi?->status_masuk ? (($absensi?->jam_masuk ? $absensi->jam_masuk.' - ' : '').$absensi->status_masuk) : ($absensi?->jam_masuk ?? '-') }}</td></tr>
            <tr><th>Absen Harian Pulang</th><td>{{ $absensi?->status_pulang ? (($absensi?->jam_pulang ? $absensi->jam_pulang.' - ' : '').$absensi->status_pulang) : ($absensi?->jam_pulang ?? '-') }}</td></tr>
        </table>
        <a class="btn" href="/dashboard/piket/absensi/{{ $siswa->id }}/edit?tanggal={{ $tanggal }}">Edit</a>
        <a class="btn muted-btn" href="/dashboard/piket/absensi-harian?tanggal={{ $tanggal }}">Kembali</a>
    </section>
</main>
</body>
</html>
