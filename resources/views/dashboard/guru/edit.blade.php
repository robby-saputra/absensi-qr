<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Edit Guru</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-guru-edit.css') }}">
</head>

<body>

    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        <div class="box">
            <h2>Edit Guru</h2>

            @if ($errors->any())
                <div class="error">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="/dashboard/admin/guru/update/{{ $guru->id }}">
                @csrf

                <label>Nama</label>
                <input type="text" name="nama" value="{{ old('nama', $guru->nama) }}">

                <label>NUPTK</label>
                <input type="text" name="nuptk" value="{{ old('nuptk', $guru->nuptk) }}">

                <label>Username</label>
                <input type="text" name="username" value="{{ old('username', $guru->username) }}">

                <label>Password Baru</label>
                <input type="password" name="password" placeholder="Kosongkan jika tidak diganti">

                <a class="btn back" href="/dashboard/admin/guru">Kembali</a>
                <button class="btn" type="submit">Simpan</button>
            </form>
        </div>
    </main>

</body>

</html>
