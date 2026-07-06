<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Absensi Harian</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        <div class="rekap-head">
            <div>
                <h1>Detail Absensi Harian</h1>
                <p>{{ $absensi->nama_siswa }} - {{ $absensi->nama_kelas ?? '-' }}</p>
            </div>
            <div>
                <a href="/dashboard/admin/absensi" class="btn back">Kembali</a>
                <a href="/dashboard/admin/absensi/edit/{{ $absensi->id }}" class="btn">Edit</a>
            </div>
        </div>

        <table>
            <tr>
                <th>Nama</th>
                <td>{{ $absensi->nama_siswa }}</td>
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
                <th>Jurusan</th>
                <td>{{ $absensi->nama_jurusan ?? '-' }}</td>
            </tr>
            <tr>
                <th>Tahun Ajaran</th>
                <td>{{ $absensi->tahun_ajaran ?? '-' }} {{ $absensi->semester ? '- ' . ucfirst($absensi->semester) : '' }}
                </td>
            </tr>
            <tr>
                <th>Tanggal</th>
                <td>{{ $absensi->tanggal }}</td>
            </tr>
            <tr>
                <th>Jam Masuk</th>
                <td>{{ $absensi->jam_masuk ?? '-' }}</td>
            </tr>
            <tr>
                <th>Status Masuk</th>
                <td><span class="status-pill">{{ $absensi->status_masuk ?? '-' }}</span></td>
            </tr>
            <tr>
                <th>Jam Pulang</th>
                <td>{{ $absensi->jam_pulang ?? '-' }}</td>
            </tr>
            <tr>
                <th>Status Pulang</th>
                <td><span class="status-pill">{{ $absensi->status_pulang ?? '-' }}</span></td>
            </tr>
        </table>
    </main>
</body>

</html>
