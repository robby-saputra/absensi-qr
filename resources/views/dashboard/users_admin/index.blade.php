<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Users</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>
<body>
@include('layouts.sidebar_admin')

<main id="content" class="content">
    <div class="rekap-head">
        <div>
            <h1>Kelola Users</h1>
            <p>CRUD user khusus superadmin. Admin utama saat ini: Devi.</p>
        </div>
        <div>
            <a class="btn back" href="/dashboard/admin">Kembali</a>
            <a class="btn" href="/dashboard/admin/users/create">Tambah User</a>
        </div>
    </div>

    <table>
        <tr>
            <th>Nama</th>
            <th>Username</th>
            <th>Role</th>
            <th>Level</th>
            <th>Kelas</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
        @forelse($users as $u)
            <tr>
                <td>{{ $u->nama }}</td>
                <td>{{ $u->username }}</td>
                <td><span class="status-pill">{{ $u->role }}</span></td>
                <td>{{ $u->admin_level ?? '-' }}</td>
                <td>{{ $u->kelasRelasi->nama_kelas ?? '-' }}</td>
                <td>{{ $u->aktif ? 'Aktif' : 'Nonaktif' }}</td>
                <td>
                    <a class="btn" href="/dashboard/admin/users/edit/{{ $u->id }}">Edit</a>
                    <a class="btn back" href="/dashboard/admin/users/{{ $u->id }}/reset-password">Reset Password</a>
                    @if(!($u->role === 'admin' && $u->admin_level === 'superadmin'))
                        <a class="btn btn-danger confirm-delete" href="/dashboard/admin/users/delete/{{ $u->id }}">Hapus</a>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty-row">Data user belum tersedia.</td></tr>
        @endforelse
    </table>
</main>
</body>
</html>
