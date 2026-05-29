<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Guru Piket</title>
<link rel="stylesheet" href="{{ asset('css/pages/dashboard-piket.css') }}">
</head>
<body>
@include('layouts.sidebar_piket')

<main id="content" class="content" data-print-title="Rekap Guru Piket" data-print-date="{{ now()->format('d-m-Y H:i') }}">
    <div class="page-head">
        <div>
            <h2>Dashboard Guru Piket</h2>
            <p>{{ $user->nama }} - {{ now()->locale('id')->translatedFormat('l, d F Y') }}</p>
        </div>
        @if(in_array($activePiketPage, ['absensi','riwayat','jadwal']))
            <div>
                <button type="button" class="btn" onclick="printReport('Rekap Guru Piket')">Print Rekap</button>
                <button type="button" class="btn" onclick="exportTableToExcel('rekap-guru-piket', 'Rekap Guru Piket')">Excel</button>
            </div>
        @endif
    </div>

    @if(session('success')) <div class="alert success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert error">{{ session('error') }}</div> @endif

    @if(in_array($activePiketPage, ['dashboard','qr']))
    <section class="grid qr-section">
        <div class="card">
            <h3>Generate QR Absensi Harian</h3>
            <form method="POST" action="/dashboard/piket/generate-qr" class="inline-form">
                @csrf
                <select name="tipe">
                    <option value="masuk" {{ ($tipe ?? 'masuk') == 'masuk' ? 'selected' : '' }}>Masuk</option>
                    <option value="pulang" {{ ($tipe ?? 'masuk') == 'pulang' ? 'selected' : '' }}>Pulang</option>
                </select>
                <button class="btn" type="submit">Generate QR</button>
            </form>
        </div>

        <div class="card">
            <h3>QR Hari Ini</h3>
            @if($qr)
                <p><b>Tipe:</b> {{ ucfirst($qr->tipe) }}</p>
                <p><b>Token:</b> {{ $qr->token }}</p>
                <div class="qr-box">
                    <img src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(260)->margin(2)->generate($qr->token)) }}" alt="QR Code">
                </div>
            @else
                <p>Belum ada QR hari ini.</p>
            @endif
        </div>
    </section>
    @endif

    @if(in_array($activePiketPage, ['dashboard','absensi']))
    <section class="card">
        <h3>Absensi Harian Siswa</h3>
        <p class="muted">Data absen masuk dan pulang harian yang dipakai guru mapel untuk melihat kehadiran siswa di kelas ajarnya.</p>

        <form method="GET" class="filter-box">
            <input type="hidden" name="page" value="{{ $activePiketPage }}">
            <label>Tanggal <input type="date" name="tanggal" value="{{ $tanggalFilter }}"></label>
            <label>Kelas
                <select name="kelas_id">
                    <option value="">Semua Kelas</option>
                    @foreach($kelas as $k)
                        <option value="{{ $k->id }}" {{ (string)$kelasFilter === (string)$k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn" type="submit">Tampilkan</button>
            <a class="btn muted-btn" href="/dashboard/piket?page={{ $activePiketPage }}">Reset</a>
        </form>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>Nama</th><th>NIS</th><th>Kelas</th><th>Absen Harian Masuk</th><th>Absen Harian Pulang</th><th>Aksi</th>
                </tr>
                @forelse($absensiSiswa as $a)
                    @php
                        $khusus = in_array($a->status_masuk, ['izin','sakit']) ? $a->status_masuk : (in_array($a->status_pulang, ['izin','sakit']) ? $a->status_pulang : null);
                        $masuk = $khusus ?? $a->status_masuk;
                        $pulang = $khusus ?? $a->status_pulang;
                    @endphp
                    <tr>
                        <td>{{ $a->nama }}</td>
                        <td>{{ $a->nis ?? '-' }}</td>
                        <td>{{ $a->nama_kelas ?? '-' }}</td>
                        <td>{{ $masuk ? (($a->jam_masuk ? $a->jam_masuk.' - ' : '').$masuk) : ($a->jam_masuk ?? '-') }}</td>
                        <td>{{ $pulang ? (($a->jam_pulang ? $a->jam_pulang.' - ' : '').$pulang) : ($a->jam_pulang ?? '-') }}</td>
                        <td>
                            <a class="btn" href="/dashboard/piket/absensi/{{ $a->id }}/view?tanggal={{ $tanggalFilter }}">View</a>
                            <a class="btn muted-btn" href="/dashboard/piket/absensi/{{ $a->id }}/edit?tanggal={{ $tanggalFilter }}">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada data siswa.</td></tr>
                @endforelse
            </table>
        </div>
    </section>
    @endif

    @if($activePiketPage === 'dashboard')
    <section class="grid">
        <div class="card"><h3>Total Siswa</h3><h2>{{ $totalSiswa }}</h2></div>
        <div class="card"><h3>QR Aktif</h3><h2>{{ $qr ? '1' : '0' }}</h2></div>
        <div class="card"><h3>Status</h3><h2 class="text-success">Aktif</h2></div>
    </section>
    @endif

    @if($activePiketPage === 'riwayat')
    <section class="card">
        <h3>Riwayat Absensi Harian</h3>
        <div class="table-wrap">
            <table>
                <tr><th>Tanggal</th><th>Nama</th><th>NIS</th><th>Kelas</th><th>Masuk</th><th>Pulang</th></tr>
                @forelse($riwayatAbsensi as $r)
                    <tr>
                        <td>{{ $r->tanggal }}</td><td>{{ $r->nama }}</td><td>{{ $r->nis ?? '-' }}</td><td>{{ $r->nama_kelas ?? '-' }}</td>
                        <td>{{ $r->status_masuk ? (($r->jam_masuk ? $r->jam_masuk.' - ' : '').$r->status_masuk) : '-' }}</td>
                        <td>{{ $r->status_pulang ? (($r->jam_pulang ? $r->jam_pulang.' - ' : '').$r->status_pulang) : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada riwayat.</td></tr>
                @endforelse
            </table>
        </div>
    </section>
    @endif

    @if($activePiketPage === 'jadwal')
    <section class="card">
        <h3>Rekap Jadwal Guru Piket</h3>
        <div class="table-wrap">
            <table>
                <tr><th>Guru</th><th>Pengganti 1</th><th>Pengganti 2</th><th>Hari</th><th>Jam</th><th>Status</th></tr>
                @foreach($rekapJadwalPiket as $j)
                    <tr>
                        <td>{{ $j->guru_utama }}</td><td>{{ $j->guru_pengganti ?? '-' }}</td><td>{{ $j->guru_pengganti2 ?? '-' }}</td>
                        <td>{{ ucfirst($j->hari) }}</td><td>{{ $j->jam_mulai }} - {{ $j->jam_selesai }}</td><td>{{ $j->status }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </section>
    @endif
</main>
</body>
</html>
