<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Detail Absensi Siswa</title>
<link rel="stylesheet" href="{{ asset('css/pages/dashboard-guru.css') }}">
</head>
<body>
@include('layouts.sidebar_guru')

<main id="content" class="content">
    <div class="attendance-panel">
        <div class="section-head">
            <div>
                <h3>Detail Absensi Siswa</h3>
                <p>Detail absensi harian pada tanggal {{ $tanggal }}.</p>
            </div>
            <a href="/dashboard/guru/verifikasi-absensi?tanggal={{ $tanggal }}" class="btn disabled">Kembali</a>
        </div>

        <table>
            <tr><th>Nama</th><td>{{ $siswa->nama }}</td></tr>
            <tr><th>NIS</th><td>{{ $siswa->nis ?? '-' }}</td></tr>
            <tr><th>Kelas</th><td>{{ $kelas->nama_kelas ?? '-' }}</td></tr>
            <tr><th>Tanggal</th><td>{{ $tanggal }}</td></tr>
            <tr><th>Jam Masuk</th><td>{{ $absensi->jam_masuk ?? '-' }}</td></tr>
            <tr><th>Status Masuk</th><td>{{ $absensi->status_masuk ?? 'belum absen' }}</td></tr>
            <tr><th>Jam Pulang</th><td>{{ $absensi->jam_pulang ?? '-' }}</td></tr>
            <tr><th>Status Pulang</th><td>{{ $absensi->status_pulang ?? 'belum pulang' }}</td></tr>
            <tr><th>Terakhir Diubah</th><td>{{ $absensi->updated_at ?? '-' }}</td></tr>
        </table>

        <a href="/dashboard/guru/absensi/{{ $siswa->id }}/edit?tanggal={{ $tanggal }}" class="btn btn-purple">
            Edit Absensi
        </a>
    </div>
</main>
</body>
</html>
