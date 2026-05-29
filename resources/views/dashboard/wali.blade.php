<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Wali Kelas</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-wali.css') }}">
</head>
<body>

@include('layouts.sidebar_wali')

<div id="content" class="content">

    <div class="topbar">

        <h2>Dashboard Wali Kelas</h2>

        <br>

        <p>
            Selamat datang,
            <strong>{{ $user->nama }}</strong>
        </p>

        <p>
            Kelas Wali:
            <strong>{{ $wali->nama_kelas }}</strong>
        </p>

    </div>

    <div class="cards">

        <div class="card">
            <h3>Total Siswa</h3>
            <p>{{ count($siswa) }}</p>
        </div>

        <div class="card">
            <h3>Kelas</h3>
            <p>{{ $wali->nama_kelas }}</p>
        </div>

    </div>

    <div class="table-box">

        <h3>Data Siswa</h3>

        <table>

            <tr>
                <th>Nama</th>
                <th>Username</th>
                <th>Kelas</th>
                <th>Nama Orang Tua</th>
                <th>No Orang Tua</th>
                <th>Status</th>
            </tr>

            @foreach($siswa as $s)

                <tr>

                    <td>{{ $s->nama }}</td>

                    <td>{{ $s->username }}</td>

                    <td>{{ $s->nama_kelas ?? '-' }}</td>

                    <td>{{ $s->nama_ortu ?? '-' }}</td>

                    <td>{{ $s->no_ortu }}</td>

                    <td>
                       @if($s->status_hari_ini == 'hadir')

    <span class="badge"
          class="metric-green">
        Hadir
    </span>

@elseif($s->status_hari_ini == 'telat')

    <span class="badge"
          class="metric-orange">
        Telat
    </span>

@else

    <span class="badge"
          class="metric-red">
        Belum Absen
    </span>

@endif
                    </td>

                </tr>

            @endforeach

        </table>

    </div>

</div>

</body>
</html>




