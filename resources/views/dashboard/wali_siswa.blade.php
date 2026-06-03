<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Data Siswa</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-wali_siswa.css') }}">
</head>

<body>

    @include('layouts.sidebar_wali')

    <div id="content" class="content">
        @include('layouts.alerts')

        <div class="topbar">

            <h2>Data Siswa</h2>

            <br>

            <p>
                Kelas:
                <strong>{{ $wali->nama_kelas }}</strong>
            </p>
            <p>
                <a href="/dashboard/wali/siswa">Siswa Aktif</a>
                <a href="/dashboard/wali/siswa?status=nonaktif">Siswa Nonaktif ({{ $siswaNonaktifCount ?? 0 }})</a>
                <a href="/dashboard/wali/pdf/siswa" target="_blank">PDF Resmi</a>
            </p>

        </div>

        <div class="table-box">

            <table>

                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Kelas</th>
                    <th>Nama Orang Tua</th>
                    <th>No Orang Tua</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>

                @foreach ($siswa as $s)
                    <tr>
                        <td>{{ $s->nama }}</td>
                        <td>{{ $s->username }}</td>
                        <td>{{ $s->nama_kelas ?? '-' }}</td>
                        <td>{{ $s->nama_ortu ?? '-' }}</td>
                        <td>{{ $s->no_ortu }}</td>
                        <td>{{ $s->aktif ? 'Aktif' : 'Nonaktif' }}</td>
                        <td><a href="/dashboard/wali/siswa/detail/{{ $s->id }}">View</a></td>
                    </tr>
                @endforeach

            </table>

        </div>

    </div>

</body>

</html>
