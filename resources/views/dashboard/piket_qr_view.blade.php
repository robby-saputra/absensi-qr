{{-- File ini menampilkan QR dari sisi guru piket untuk mendukung proses pemindaian atau pengecekan kehadiran. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Tim Piket {{ ucfirst($qr->tipe) }}</title>
    <link rel="stylesheet"
        href="{{ asset('css/pages/dashboard-piket-qr-view.css') }}?v={{ filemtime(public_path('css/pages/dashboard-piket-qr-view.css')) }}">
</head>

<body>
    <main class="qr-view-page">
        <section class="qr-stage">
            <div class="qr-info">
                <span class="kicker">QR Absensi Harian</span>
                <h1>QR Tim Piket {{ ucfirst($qr->tipe) }}</h1>
                <p>
                    QR ini digunakan siswa untuk absen {{ strtolower($qr->tipe) }} harian.
                    Cukup tampilkan halaman ini di layar besar agar siswa dapat scan dengan jelas.
                </p>

                <div class="info-grid">
                    <div>
                        <span>Tanggal</span>
                        <strong>{{ \Carbon\Carbon::parse($qr->tanggal)->locale('id')->translatedFormat('l, d F Y') }}</strong>
                    </div>
                    <div>
                        <span>Berlaku Sampai</span>
                        <strong>{{ $qr->expires_at ? \Carbon\Carbon::parse($qr->expires_at)->format('H:i') : '-' }}</strong>
                    </div>
                    <div>
                        <span>Dibuat Oleh</span>
                        <strong>{{ $pembuatQr ?: '-' }}</strong>
                    </div>
                    <div>
                        <span>Token</span>
                        <strong>{{ $qr->token }}</strong>
                    </div>
                </div>

                @if ($anggotaTim->isNotEmpty())
                    <div class="team-panel">
                        <div class="team-title">
                            <span>Tim Guru Piket</span>
                            <strong>{{ ucfirst($anggotaTim->first()->hari) }} |
                                {{ substr($anggotaTim->first()->jam_mulai, 0, 5) }} -
                                {{ substr($anggotaTim->first()->jam_selesai, 0, 5) }}</strong>
                        </div>
                        <div class="team-list">
                            @foreach ($anggotaTim as $anggota)
                                <span>{{ $anggota->guru_utama }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="actions">
                    <a href="/dashboard/piket?tipe={{ $qr->tipe }}&page=qr" class="btn muted">Kembali</a>
                    <button class="btn" type="button" onclick="window.print()">Print QR</button>
                </div>
            </div>

            <div class="qr-large-card">
                <span class="qr-type">{{ ucfirst($qr->tipe) }}</span>
                <div class="qr-large-box">
                    <img src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(620)->margin(2)->generate($qr->token)) }}"
                        alt="QR Tim Piket {{ ucfirst($qr->tipe) }}">
                </div>
                <strong>Scan QR Absensi {{ ucfirst($qr->tipe) }}</strong>
                <p>Pastikan aplikasi siswa membaca QR ini sampai muncul status berhasil.</p>
            </div>
        </section>
    </main>
</body>

</html>
