<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-reset_password.css') }}">
</head>

<body>

    @include('layouts.sidebar_admin')

    <main id="content" class="content">
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

                <a class="btn back"
                    href="{{ $target->role === 'guru' ? '/dashboard/admin/guru' : '/dashboard/admin/siswa' }}">Kembali</a>
                <button class="btn" type="submit">Reset</button>
            </form>
        </div>
    </main>

</body>

</html>
