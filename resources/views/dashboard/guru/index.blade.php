
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Guru</title>
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
            padding:12px;
            text-align:left;
        }

        th{
            background:#273c75;
            color:white;
        }

        a{
            text-decoration:none;
        }

        .btn{
            padding:8px 14px;
            background:#273c75;
            color:white;
            border-radius:5px;
        }

        .danger{
            color:red;
        }

        .success{color:green;margin-bottom:15px;}
        .error{color:red;margin-bottom:15px;}
        .inline{display:inline;}
        button.link{background:none;border:none;color:#273c75;cursor:pointer;padding:0;font:inherit;}
    </style>
</head>
<body>

<h2>Kelola Guru</h2>

<p>
    <a class="btn" href="/dashboard/admin">Kembali</a>
    <a class="btn" href="/dashboard/admin/guru/create">Tambah Guru</a>
</p>

@if(session('success'))
    <div class="success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="error">{{ session('error') }}</div>
@endif

<table>
    <tr>
        <th>ID</th>
        <th>Nama</th>
        <th>NUPTK</th>
        <th>Username</th>
        <th>Role</th>
        <th>Status</th>
        <th>Aksi</th>
    </tr>

    @foreach($guru as $g)
        <tr>
            <td>{{ $g->id }}</td>
            <td>{{ $g->nama }}</td>
            <td>{{ $g->nuptk }}</td>
            <td>{{ $g->username }}</td>
            <td>{{ $g->role }}</td>
            <td>{{ ($g->aktif ?? true) ? 'Aktif' : 'Nonaktif' }}</td>
            <td>
                <a href="/dashboard/admin/guru/edit/{{ $g->id }}">Edit</a>
                |
                <a href="/dashboard/admin/users/{{ $g->id }}/reset-password">Reset Password</a>
                |
                <form class="inline" method="POST" action="/dashboard/admin/users/{{ $g->id }}/toggle-active">
                    @csrf
                    <button class="link" type="submit">
                        {{ ($g->aktif ?? true) ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
                |
                <a class="danger" href="/dashboard/admin/guru/delete/{{ $g->id }}">Hapus</a>
            </td>
        </tr>
    @endforeach

</table>

</body>
</html>
