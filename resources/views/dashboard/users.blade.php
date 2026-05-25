<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Siswa</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-users.css') }}">
</head>

<body>

<div class="header">
    <h2>Dashboard Siswa</h2>
</div>

<div class="container">

    <!-- USER INFO -->
    <div class="card">
        <h3>Selamat datang, {{ $user->nama }}</h3>
        <p>Role: {{ $user->role }}</p>
        <a href="/logout">Logout</a>
    </div>

    <!-- MANUAL ABSENSI TEST -->
    <div class="card">
        <h3>Absensi Manual (Testing)</h3>
        <p>Ini hanya untuk test integrasi sebelum Android</p>

        <form method="POST" action="/absensi/manual">
            @csrf

            <label>Token QR:</label><br>
            <input type="text" name="token" class="token-input" placeholder="Masukkan token QR"><br><br>

            <button class="btn" type="submit">Absen Sekarang</button>
        </form>
    </div>

    <!-- STATUS ABSENSI -->
    <div class="card">
        <h3>Status Hari Ini</h3>

        @php
            $absensi = \App\Models\Absensi::where('id_siswa', $user->id)
                ->whereDate('tanggal', now()->toDateString())
                ->first();
        @endphp

        @if($absensi)
            <p>Masuk:
                <span class="badge {{ $absensi->status_masuk }}">
                    {{ $absensi->jam_masuk ?? '-' }} ({{ $absensi->status_masuk }})
                </span>
            </p>

            <p>Pulang:
                <span class="badge {{ $absensi->status_pulang }}">
                    {{ $absensi->jam_pulang ?? '-' }}
                </span>
            </p>
        @else
            <p>Belum ada absensi hari ini</p>
        @endif
    </div>

</div>

</body>
</html>



