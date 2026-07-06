<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Jurusan</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-jurusan-edit.css') }}">
</head>

<body>

    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        <div class="box">
            <h2>Edit Jurusan</h2>

            @if ($errors->any())
                <div class="error">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="/dashboard/admin/jurusan/update/{{ $jurusan->id }}">
                @csrf

                <label>Nama Jurusan</label>
                <input type="text" name="nama_jurusan" value="{{ old('nama_jurusan', $jurusan->nama_jurusan) }}">

                <label>Kode Jurusan</label>
                <input type="text" name="kode_jurusan" value="{{ old('kode_jurusan', $jurusan->kode_jurusan) }}">

                <a class="btn back" href="/dashboard/admin/jurusan">Kembali</a>
                <button class="btn" type="submit">Simpan</button>
            </form>
        </div>
    </main>

</body>

</html>
