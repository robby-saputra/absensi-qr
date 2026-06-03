<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Detail Absensi Mapel</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        <div class="rekap-head">
            <div>
                <h1>Detail Absensi Mapel</h1>
                <p>{{ $absensi->nama_siswa ?? 'Siswa tidak ditemukan' }} - {{ $absensi->nama_mapel ?? 'Mapel tidak ditemukan' }}</p>
            </div>
            <div>
                <a href="/dashboard/admin/absensi-mapel" class="btn back">Kembali</a>
                <a href="/dashboard/admin/absensi-mapel/edit/{{ $absensi->id }}" class="btn">Edit</a>
            </div>
        </div>

        <table>
            <tr>
                <th>Nama Siswa</th>
                <td>{{ $absensi->nama_siswa ?? '-' }}</td>
            </tr>
            <tr>
                <th>NIS</th>
                <td>{{ $absensi->nis ?? '-' }}</td>
            </tr>
            <tr>
                <th>Kelas</th>
                <td>{{ $absensi->nama_kelas ?? '-' }}</td>
            </tr>
            <tr>
                <th>Tahun Ajaran</th>
                <td>{{ $absensi->tahun_ajaran ?? '-' }} {{ $absensi->semester ? '- ' . ucfirst($absensi->semester) : '' }}
                </td>
            </tr>
            <tr>
                <th>Mapel</th>
                <td>{{ $absensi->nama_mapel ?? '-' }}</td>
            </tr>
            <tr>
                <th>Guru Utama</th>
                <td>{{ $absensi->guru_utama ?? '-' }}</td>
            </tr>
            <tr>
                <th>Guru Pengganti</th>
                <td>{{ $absensi->guru_pengganti ?? '-' }}</td>
            </tr>
            <tr>
                <th>Jadwal</th>
                <td>{{ $absensi->hari ? ucfirst($absensi->hari) : '-' }} {{ $absensi->jam_mulai ?? '-' }} - {{ $absensi->jam_selesai ?? '-' }}</td>
            </tr>
            <tr>
                <th>Tanggal</th>
                <td>{{ $absensi->tanggal }}</td>
            </tr>
            <tr>
                <th>Jam Scan</th>
                <td>{{ $absensi->jam_scan ?? '-' }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td><span class="status-pill">{{ $absensi->status }}</span></td>
            </tr>
        </table>
    </main>
</body>

</html>
