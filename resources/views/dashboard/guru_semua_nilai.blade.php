<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Semua Nilai</title>

    <style>
        body{
            font-family:Arial;
            background:#f5f6fa;
            padding:30px;
        }

        table{
            width:100%;
            border-collapse:collapse;
            background:white;
        }

        th, td{
            border:1px solid #ddd;
            padding:10px;
        }

        th{
            background:#e67e22;
            color:white;
        }
    </style>
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
