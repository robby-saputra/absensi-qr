{{-- File ini menampilkan form tambah data guru baru yang digunakan admin untuk melengkapi data tenaga pengajar. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Guru</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-guru-create.css') }}">
</head>

<body>

    @include('layouts.sidebar_admin')

    <main id="content" class="content">

        <div class="box">

            <h2>Tambah Guru</h2>

            <form method="POST" action="/dashboard/admin/guru/store">
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
                    <option value="guru">Guru</option>
                </select>

                <button type="submit">Simpan</button>

            </form>

        </div>

    </main>

</body>

</html>
