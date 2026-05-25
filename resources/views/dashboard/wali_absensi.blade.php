<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Absensi Siswa</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-wali_absensi.css') }}">
</head>
<body>

<div class="sidebar">

    <h2>Wali Kelas</h2>

    <a href="/dashboard/wali">Dashboard</a>
    <a href="/dashboard/wali/siswa">Data Siswa</a>
    <a href="/dashboard/wali/nilai">Monitoring Nilai</a>
    <a href="/dashboard/wali/absensi">Absensi Siswa</a>
    <a href="/logout">Logout</a>

</div>

<div class="content">

    <div class="topbar">

        <h2>Absensi Siswa</h2>

        <br>

        <p>
            Kelas:
            <strong>{{ $wali->nama_kelas }}</strong>
        </p>

    </div>

    <div class="table-box">

        <table>

            <tr>
                <th>Nama</th>
                <th>Tanggal</th>
                <th>Jam Masuk</th>
                <th>Status</th>
            </tr>

            @foreach($absensi as $a)

                <tr>
                    <td>{{ $a->nama }}</td>
                    <td>{{ $a->tanggal }}</td>
                    <td>{{ $a->jam_masuk }}</td>
                    <td>{{ $a->status_masuk }}</td>
                </tr>

            @endforeach

        </table>

    </div>

</div>

</body>
</html>



