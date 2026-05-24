<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>QR Sesi Guru</title>
    <style>
        body{
            font-family:Arial;
            background:#f5f6fa;
            padding:30px;
        }

        .box{
            width:600px;
            margin:auto;
            background:white;
            padding:25px;
            border-radius:8px;
            text-align:center;
        }

        .btn{
            display:inline-block;
            padding:10px 16px;
            background:#273c75;
            color:white;
            text-decoration:none;
            border-radius:5px;
            margin-top:15px;
        }
    </style>
</head>
<body>

<div class="box">

    <h2>QR Absensi Sesi</h2>

    <p><strong>Kelas:</strong> {{ $detail->nama_kelas }}</p>
    <p><strong>Mapel:</strong> {{ $detail->nama_mapel }}</p>
    <p><strong>Jam:</strong> {{ $detail->jam_mulai }} - {{ $detail->jam_selesai }}</p>

    <div style="margin:20px 0;">
        {!! QrCode::size(250)->generate($qr->token) !!}
    </div>

    <p><strong>Token:</strong> {{ $qr->token }}</p>

    <a class="btn" href="/dashboard/guru">Kembali</a>

</div>

</body>
</html>