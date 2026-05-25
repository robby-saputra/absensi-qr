<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Semua Nilai</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-guru_semua_nilai.css') }}">
</head>
<body>

<h2>Semua Nilai</h2>

<table>

    <tr>
        <th>Siswa</th>
        <th>Kelas</th>
        <th>Mapel</th>
        <th>Jenis</th>
        <th>Nilai</th>
        <th>Keterangan</th>
        <th>Tanggal</th>
    </tr>

    @foreach($nilai as $n)
        <tr>
            <td>{{ $n->nama_siswa }}</td>
            <td>{{ $n->nama_kelas ?? '-' }}</td>
            <td>{{ $n->nama_mapel }}</td>
            <td>{{ $n->jenis_nilai }}</td>
            <td>{{ $n->nilai }}</td>
            <td>{{ $n->keterangan }}</td>
            <td>{{ $n->created_at }}</td>
        </tr>
    @endforeach

</table>

</body>
</html>




