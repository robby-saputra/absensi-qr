<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <style>
        body{font-family:Arial;background:#f5f6fa;padding:30px;}
        .box{width:480px;margin:auto;background:white;padding:25px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.08);}
        input{width:100%;padding:10px;margin:6px 0 15px;box-sizing:border-box;}
        .btn{padding:10px 16px;background:#273c75;color:white;border:none;border-radius:5px;text-decoration:none;cursor:pointer;}
        .back{background:#7f8fa6;}
        .error{background:#e84118;color:white;padding:10px;border-radius:5px;margin-bottom:15px;}
    </style>
</head>
<body>
<div class="box">
    <h2>Reset Password</h2>
    <p>{{ $target->nama }} ({{ $target->username }})</p>

    @if ($errors->any())
        <div class="error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="/dashboard/admin/users/{{ $target->id }}/reset-password">
        @csrf

        <label>Password Baru</label>
        <input type="password" name="password">

        <label>Konfirmasi Password</label>
        <input type="password" name="password_confirmation">

        <a class="btn back" href="{{ $target->role === 'guru' ? '/dashboard/admin/guru' : '/dashboard/admin/siswa' }}">Kembali</a>
        <button class="btn" type="submit">Reset</button>
    </form>
</div>
</body>
</html>
