<!DOCTYPE html>
<html>
<head>
    <title>Login Absensi QR</title>
    <link rel="stylesheet" href="{{ asset('css/pages/login.css') }}">
</head>
<body>

<h2>Login Sistem Absensi</h2>

@if(session('error'))
    <p class="error-text">{{ session('error') }}</p>
@endif

<form method="POST" action="/login">
    @csrf

    <label>Username</label><br>
    <input type="text" name="username" required><br><br>

    <label>Password</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Login</button>
</form>

</body>
</html>



