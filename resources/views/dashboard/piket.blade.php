<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Piket</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-piket.css') }}">
</head>
<body>

<div class="header">
    <h2>Dashboard Piket</h2>
    <a href="/logout">Logout</a>
</div>

<div class="container">

    @if(session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert error">{{ session('error') }}</div>
    @endif

    <!-- USER INFO -->
    <div class="card">
        <h3>Selamat datang, {{ $user->nama }}</h3>
        <p>Role: {{ $user->role == 'guru' ? 'Guru Piket' : 'Piket (Super Admin QR Generator)' }}</p>

        @if($jadwalPiketHariIni)
            <p class="notice">
                Anda bertugas sebagai guru piket hari ini,
                {{ ucfirst($jadwalPiketHariIni->hari) }}
                pukul {{ $jadwalPiketHariIni->jam_mulai }} - {{ $jadwalPiketHariIni->jam_selesai }}.
            </p>

            @if(!empty($jadwalPiketHariIni->status_dipilih_at))
                <p class="notice locked">
                    Status sudah dipilih: {{ $jadwalPiketHariIni->status }}.
                    Pilihan ini sudah dikunci.
                </p>
            @else
                <form method="POST" action="/dashboard/piket/status" class="status-form">
                    @csrf
                    <button type="submit" name="status" value="hadir" class="btn btn-hadir">
                        Hadir
                    </button>
                    <button type="submit" name="status" value="izin" class="btn btn-izin">
                        Izin
                    </button>
                    <button type="submit" name="status" value="sakit" class="btn btn-sakit">
                        Sakit
                    </button>
                </form>
            @endif
        @endif

        @if($jadwalMenggantikanHariIni->isNotEmpty())
            @foreach($jadwalMenggantikanHariIni as $jadwalGanti)
                <p class="notice warning">
                    Anda menjadi guru piket pengganti untuk {{ $jadwalGanti->guru_digantikan }}
                    karena statusnya {{ $jadwalGanti->status }}.
                    Jadwal: {{ ucfirst($jadwalGanti->hari) }}
                    {{ $jadwalGanti->jam_mulai }} - {{ $jadwalGanti->jam_selesai }}.
                </p>
            @endforeach
        @endif
    </div>

    <!-- STATISTIC -->
    <div class="grid">

        <div class="card">
            <h3>Total Siswa</h3>
            <h2>{{ $totalSiswa }}</h2>
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

    <div class="card">
        <h3>Absensi Harian Siswa</h3>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>NIS</th>
                        <th>Kelas</th>
                        <th>Jam Masuk</th>
                        <th>Status Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Status Pulang</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($absensiSiswa as $a)
                        <tr>
                            <td>{{ $a->nama }}</td>
                            <td>{{ $a->nis ?? '-' }}</td>
                            <td>{{ $a->nama_kelas ?? '-' }}</td>
                            <td>{{ $a->jam_masuk ?? '-' }}</td>
                            <td>
                                <span class="status {{ $a->status_masuk ? 'filled' : 'empty' }}">
                                    {{ $a->status_masuk ?? 'belum absen' }}
                                </span>
                            </td>
                            <td>{{ $a->jam_pulang ?? '-' }}</td>
                            <td>
                                <span class="status {{ $a->status_pulang ? 'filled' : 'empty' }}">
                                    {{ $a->status_pulang ?? 'belum pulang' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">Belum ada data siswa.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>



