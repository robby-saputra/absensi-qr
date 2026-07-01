{{-- File ini menampilkan halaman QR guru yang digunakan sebagai akses atau identitas pemindaian pada sistem absensi. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Sesi Guru</title>
    <link rel="stylesheet"
        href="{{ asset('css/pages/dashboard-guru_qr.css') }}?v={{ filemtime(public_path('css/pages/dashboard-guru_qr.css')) }}">
</head>

<body>
    @include('layouts.sidebar_guru')

    <main id="content" class="content">
        <div class="box qr-session-card">

            <span class="kicker">QR Mapel</span>
            <h2>QR Absensi Sesi</h2>

            <div class="session-meta">
                <div><span>Kelas</span><strong>{{ $detail->nama_kelas }}</strong></div>
                <div><span>Mapel</span><strong>{{ $detail->nama_mapel }}</strong></div>
                <div><span>Jam Pelajaran</span><strong>{{ labelJadwalJp($detail) }}</strong></div>
            </div>

            <div class="qr-preview">
                {!! QrCode::size(250)->generate($qr->token) !!}
            </div>

            <div class="token-box">
                <span>Token</span>
                <strong>{{ $qr->token }}</strong>
            </div>

            <div class="qr-actions">
                <a class="btn muted" href="/dashboard/guru">Kembali</a>
                <a class="btn" href="/dashboard/guru/qr/{{ $qr->id }}/view" target="_blank">View QR Besar</a>
            </div>

        </div>
    </main>

</body>

</html>
