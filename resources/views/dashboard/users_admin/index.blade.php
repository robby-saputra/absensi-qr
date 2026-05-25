<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Users</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-users_admin-index.css') }}">
</head>
<body>

@include('layouts.sidebar_admin')

<main id="content" class="content">

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

</main>

</body>
</html>




