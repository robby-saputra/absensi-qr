<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Absensi Siswa</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-wali_absensi.css') }}">
</head>
<body>

@include('layouts.sidebar_wali')

<div id="content" class="content">

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



