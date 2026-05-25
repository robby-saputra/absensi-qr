
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Guru</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-guru-index.css') }}">
</head>
<body>

@include('layouts.sidebar_admin')

<main id="content" class="content">

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
                <div class="aksi">
                <a class="btn edit" href="/dashboard/admin/guru/edit/{{ $g->id }}">Edit</a>
                <a class="btn" href="/dashboard/admin/users/{{ $g->id }}/reset-password">Reset Password</a>
                <form class="inline" method="POST" action="/dashboard/admin/users/{{ $g->id }}/toggle-active">
                    @csrf
                    <button class="btn btn-muted" type="submit">
                        {{ ($g->aktif ?? true) ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
                <a class="btn hapus" href="/dashboard/admin/guru/delete/{{ $g->id }}">Hapus</a>
                </div>
            </td>
        </tr>
    @endforeach

</table>

</main>

</body>
</html>





