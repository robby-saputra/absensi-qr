{{-- File ini menampilkan form tambah akun admin baru agar hak akses sistem dapat dikelola dengan baik. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-users_admin-create.css') }}">
</head>

<body>

    @include('layouts.sidebar_admin')

    <main id="content" class="content">

        <div class="box">

            <h2>Tambah User</h2>

            <form method="POST" action="/dashboard/admin/users/store">

                @csrf

                <label>Nama</label>
                <input type="text" name="nama">

                <label>NUPTK</label>
                <input type="text" name="nuptk">

                <label>Username</label>
                <input type="text" name="username">

                <label>Password</label>
                <input type="text" name="password">

                <label>Role</label>
                <select name="role">
                    <option value="admin">Admin</option>
                    <option value="guru">Guru</option>
                    <option value="piket">Piket</option>
                    <option value="siswa">Siswa</option>
                </select>

                <label>Kelas</label>
                <input type="text" name="kelas">

                <label>No Orang Tua</label>
                <input type="text" name="no_ortu">

                <button type="submit">
                    Simpan
                </button>

            </form>

        </div>

    </main>

</body>

</html>
