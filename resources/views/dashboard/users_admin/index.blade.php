<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Users</title>

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
            background:#273c75;
            color:white;
        }

        .btn{
            padding:8px 14px;
            background:#273c75;
            color:white;
            text-decoration:none;
            border-radius:5px;
        }

        .hapus{
            background:red;
        }
    </style>
</head>
<body>

<h2>Kelola Users</h2>

<p>
    <a class="btn" href="/dashboard/admin">
        Kembali
    </a>

    <a class="btn" href="/dashboard/admin/users/create">
        Tambah User
    </a>
</p>

<table>

    <tr>
        <th>Nama</th>
        <th>Username</th>
        <th>Role</th>
        <th>Kelas</th>
        <th>Aksi</th>
    </tr>

    @foreach($users as $u)

        <tr>
            <td>{{ $u->nama }}</td>
            <td>{{ $u->username }}</td>
            <td>{{ $u->role }}</td>
            <td>{{ $u->kelas }}</td>

            <td>
                <a class="btn hapus"
                   href="/dashboard/admin/users/delete/{{ $u->id }}">
                    Hapus
                </a>
            </td>
        </tr>

    @endforeach

</table>

</body>
</html>