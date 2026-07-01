{{-- File ini menampilkan halaman awal aplikasi sebelum pengguna masuk ke dashboard sistem absensi QR. --}}
<!DOCTYPE html>
<html>

<head>
    @include('layouts.favicon')
    <title>Login Absensi QR</title>
    <link rel="stylesheet" href="{{ asset('css/pages/welcome.css') }}">
</head>

<body>

    <h2>Login Sistem Absensi</h2>

    @if (session('error'))
        <p class="error-text">{{ session('error') }}</p>
    @endif

    <form method="POST" action="/login">
        @csrf
        <input type="text" name="username" placeholder="Username"><br><br>
        <input type="password" name="password" placeholder="Password"><br><br>
        <button type="submit">Login</button>
    </form>

</body>

</html>
