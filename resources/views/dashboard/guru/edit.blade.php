<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Guru</title>
    <style>
        body{font-family:Arial;background:#f5f6fa;padding:30px;}
        .box{width:520px;margin:auto;background:white;padding:25px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);}
        input{width:100%;padding:10px;margin:6px 0 15px;box-sizing:border-box;}
        .btn{padding:10px 16px;background:#273c75;color:white;border:none;border-radius:5px;text-decoration:none;cursor:pointer;}
        .back{background:#7f8fa6;}
        .error{background:#e84118;color:white;padding:10px;border-radius:5px;margin-bottom:15px;}
    </style>
</head>
<body>
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
</body>
</html>
