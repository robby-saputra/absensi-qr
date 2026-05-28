<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Siswa</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-wali_siswa.css') }}">
</head>
<body>

@include('layouts.sidebar_wali')

<div id="content" class="content">

    <div class="topbar">

        <h2>Data Siswa</h2>

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
                <th>Username</th>
                <th>Kelas</th>
                <th>No Orang Tua</th>
            </tr>

            @foreach($siswa as $s)

                <tr>
                    <td>{{ $s->nama }}</td>
                    <td>{{ $s->username }}</td>
                    <td>{{ $s->nama_kelas ?? '-' }}</td>
                    <td>{{ $s->no_ortu }}</td>
                </tr>

            @endforeach

        </table>

    </div>

</div>

</body>
</html>




