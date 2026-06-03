<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Detail Absen Mapel</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-guru.css') }}">
</head>

<body>
    @include('layouts.sidebar_guru')
    <main id="content" class="content">
        @include('layouts.alerts')

        <div class="attendance-panel">
            <div class="section-head">
                <div>
                    <h3>Detail Absen Mapel</h3>
                    <p>Absensi satu kali scan untuk sesi mata pelajaran.</p>
                </div>
                <a href="/dashboard/guru/verifikasi-absensi?tanggal={{ $tanggal }}" class="btn disabled">Kembali</a>
            </div>

            <table>
                <tr>
                    <th>Nama</th>
                    <td>{{ $siswa->nama }}</td>
                </tr>
                <tr>
                    <th>NIS</th>
                    <td>{{ $siswa->nis ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Kelas</th>
                    <td>{{ $jadwal->nama_kelas }}</td>
                </tr>
                <tr>
                    <th>Mapel</th>
                    <td>{{ $jadwal->nama_mapel }}</td>
                </tr>
                <tr>
                    <th>Tanggal</th>
                    <td>{{ $tanggal }}</td>
                </tr>
                <tr>
                    <th>Jam Pelajaran</th>
                    <td>{{ $jadwal->jam_mulai }} - {{ $jadwal->jam_selesai }}</td>
                </tr>
                <tr>
                    <th>Jam Absen Mapel</th>
                    <td>{{ $absensiMapel->jam_scan ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Status Absen Mapel</th>
                    <td>{{ $absensiMapel->status ?? 'belum absen mapel' }}</td>
                </tr>
                <tr>
                    <th>Catatan Guru</th>
                    <td>{{ $absensiMapel->catatan_guru ?? '-' }}</td>
                </tr>
            </table>

            <a href="/dashboard/guru/absensi-mapel/{{ $jadwal->id }}/{{ $siswa->id }}/edit?tanggal={{ $tanggal }}"
                class="btn btn-purple">Edit Absen Mapel</a>
        </div>
    </main>
</body>

</html>
