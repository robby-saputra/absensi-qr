<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Monitoring Nilai</title>

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
            text-align:left;
        }

        th{
            background:#273c75;
            color:white;
        }

        .btn{
            padding:6px 12px;
            border-radius:5px;
            text-decoration:none;
            color:white;
        }

        .edit{
            background:orange;
        }

        .hapus{
            background:red;
        }
    </style>
</head>
<body>

<h2>Monitoring Nilai Siswa</h2>

<p>
    <a href="/dashboard/admin">Kembali</a>
</p>

<table>

    <tr>
        <th>Siswa</th>
        <th>Kelas</th>
        <th>Mapel</th>
        <th>Guru</th>
        <th>Jenis</th>
        <th>Nilai</th>
        <th>Aksi</th>
    </tr>

    @foreach($nilai as $n)
        <tr>
            <td>{{ $n->nama_siswa }}</td>
            <td>{{ $n->nama_kelas ?? '-' }}</td>
            <td>{{ $n->nama_mapel }}</td>
            <td>{{ $n->nama_guru }}</td>
            <td>{{ $n->jenis_nilai }}</td>
            <td>{{ $n->nilai }}</td>

            <td>
                <a class="btn edit"
                   href="/dashboard/admin/nilai/edit/{{ $n->id }}">
                    Edit
                </a>

                <a class="btn hapus"
                   href="/dashboard/admin/nilai/delete/{{ $n->id }}">
                    Hapus
                </a>
            </td>
        </tr>
    @endforeach

</table>

</body>
</html>
