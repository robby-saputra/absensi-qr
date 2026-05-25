<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Monitoring Nilai</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-wali_nilai.css') }}">
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

        <h2>Monitoring Nilai</h2>

        <br>

        <p>
            Kelas:
            <strong>{{ $wali->nama_kelas }}</strong>
        </p>

    </div>

    <div class="table-box">

        <table>

            <tr>
                <th>Siswa</th>
                <th>Mapel</th>
                <th>Jenis</th>
                <th>Nilai</th>
                <th>Keterangan</th>
            </tr>

            @foreach($nilai as $n)

                <tr>
                    <td>{{ $n->nama_siswa }}</td>
                    <td>{{ $n->nama_mapel }}</td>
                    <td>{{ $n->jenis_nilai }}</td>
                    <td>{{ $n->nilai }}</td>
                    <td>{{ $n->keterangan }}</td>
                </tr>

            @endforeach

        </table>

    </div>

</div>

</body>
</html>



