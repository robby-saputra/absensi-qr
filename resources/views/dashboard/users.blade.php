<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Siswa</title>

    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
            margin: 0;
        }

        .header {
            background: #111827;
            color: white;
            padding: 15px;
        }

        .container {
            padding: 20px;
        }

        .card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .btn {
            padding: 10px 15px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .btn:hover {
            background: #1d4ed8;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 5px;
            color: white;
        }

        .hadir { background: green; }
        .telat { background: orange; }
        .pulang { background: purple; }
    </style>
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
            <input type="text" name="token" style="padding:8px;width:100%" placeholder="Masukkan token QR"><br><br>

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