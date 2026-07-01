{{-- File ini menampilkan detail QR guru agar kode dapat dilihat dan digunakan sesuai kebutuhan absensi. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Absensi Mapel</title>
    <link rel="stylesheet"
        href="{{ asset('css/pages/dashboard-guru-qr-view.css') }}?v={{ filemtime(public_path('css/pages/dashboard-guru-qr-view.css')) }}">
</head>

<body>
    <main class="qr-view-page">
        <section class="qr-stage">
            <div class="qr-info">
                <span class="kicker">QR Absensi Mapel</span>
                <h1>{{ $detail->nama_mapel }}</h1>
                <p>
                    QR ini digunakan siswa untuk absen pada sesi pelajaran {{ $detail->nama_mapel }}.
                    Tampilkan halaman ini di depan kelas agar siswa bisa scan dengan jelas.
                </p>

                <div class="info-grid">
                    <div>
                        <span>Kelas</span>
                        <strong>{{ $detail->nama_kelas }}</strong>
                    </div>
                    <div>
                        <span>Jam Pelajaran</span>
                        <strong>{{ labelJadwalJp($detail) }}</strong>
                    </div>
                    <div>
                        <span>Guru Mapel</span>
                        <strong>{{ $detail->nama_guru }}</strong>
                    </div>
                    <div>
                        <span>Berlaku Sampai</span>
                        <strong>{{ $qr->expires_at ? \Carbon\Carbon::parse($qr->expires_at)->format('H:i') : '-' }}</strong>
                    </div>
                    <div>
                        <span>Tanggal</span>
                        <strong>{{ \Carbon\Carbon::parse($qr->tanggal)->locale('id')->translatedFormat('l, d F Y') }}</strong>
                    </div>
                    <div>
                        <span>Token</span>
                        <strong>{{ $qr->token }}</strong>
                    </div>
                </div>

                <div class="hint-panel">
                    <strong>Keterangan</strong>
                    <span>Siswa hanya perlu scan satu kali untuk sesi mapel ini. Jika QR kedaluwarsa, guru dapat membuka
                        ulang sesi untuk membuat QR aktif baru.</span>
                </div>

                <div class="actions">
                    <a href="/dashboard/guru/mulai-sesi/{{ $detail->id }}" class="btn muted">Kembali</a>
                    <button class="btn" type="button" onclick="window.print()">Print QR</button>
                </div>
            </div>

            <div class="qr-large-card">
                <span class="qr-type">Mapel</span>
                <div class="qr-large-box">
                    <img src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(620)->margin(2)->generate($qr->token)) }}"
                        alt="QR Absensi Mapel">
                </div>
                <strong>Scan QR Absensi Mapel</strong>
                <p>{{ $detail->nama_mapel }} | {{ $detail->nama_kelas }}</p>
            </div>
        </section>
    </main>
</body>

</html>
