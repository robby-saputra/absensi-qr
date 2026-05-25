<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>QR Sesi Guru</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-guru_qr.css') }}">
</head>
<body>

<div class="box">

    <h2>QR Absensi Sesi</h2>

    <p><strong>Kelas:</strong> {{ $detail->nama_kelas }}</p>
    <p><strong>Mapel:</strong> {{ $detail->nama_mapel }}</p>
    <p><strong>Jam:</strong> {{ $detail->jam_mulai }} - {{ $detail->jam_selesai }}</p>

    <div class="qr-preview">
        {!! QrCode::size(250)->generate($qr->token) !!}
    </div>

    <p><strong>Token:</strong> {{ $qr->token }}</p>

    <a class="btn" href="/dashboard/guru">Kembali</a>

</div>

</body>
</html>



