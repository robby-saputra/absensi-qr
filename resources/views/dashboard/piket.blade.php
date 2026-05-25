<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Piket</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-piket.css') }}">
</head>
<body>

<div class="header">
    <h2>Dashboard Piket</h2>
</div>

<div class="container">

    <!-- USER INFO -->
    <div class="card">
        <h3>Selamat datang, {{ $user->nama }}</h3>
        <p>Role: Piket (Super Admin QR Generator)</p>
    </div>

    <!-- STATISTIC -->
    <div class="grid">

        <div class="card">
            <h3>Total Siswa</h3>
            <h2>--</h2>
        </div>

        <div class="card">
            <h3>QR Hari Ini</h3>
            <h2>{{ $qr ? '1 Aktif' : '0' }}</h2>
        </div>

        <div class="card">
            <h3>Status Sistem</h3>
            <h2 class="text-success">Aktif</h2>
        </div>

    </div>

    <!-- GENERATE QR -->
    <div class="card">
        <h3>Generate QR Absensi</h3>

        <form method="POST" action="/dashboard/piket/generate-qr">
            @csrf

            <select name="tipe">
                <option value="masuk" {{ ($tipe ?? 'masuk') == 'masuk' ? 'selected' : '' }}>
                    Masuk
                </option>
                <option value="pulang" {{ ($tipe ?? 'masuk') == 'pulang' ? 'selected' : '' }}>
                    Pulang
                </option>
            </select>

            <button class="btn" type="submit">Generate QR</button>
        </form>
    </div>

    <!-- QR INFO -->
    <div class="card">
        <h3>QR Hari Ini</h3>

        @if($qr)
            <p><b>Tanggal:</b> {{ $qr->tanggal }}</p>

            <p><b>Tipe:</b>
                <span class="badge {{ $qr->tipe }}">
                    {{ $qr->tipe }}
                </span>
            </p>

            <p><b>Token:</b> {{ $qr->token }}</p>

            <div class="qr-box">
    <img
        src="data:image/png;base64,{{ base64_encode(
            QrCode::format('png')->size(320)->margin(2)->generate($qr->token)
        ) }}"
        alt="QR Code"
    >
</div>

        @else
            <p>Belum ada QR hari ini</p>
        @endif
    </div>

</div>

</body>
</html>



