<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Profil Siswa</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-admin.css') }}">
</head>
<body>
@if($layout === 'wali')
    @include('layouts.sidebar_wali')
@else
    @include('layouts.sidebar_admin')
@endif

<main id="content" class="content">
    <div class="welcome">
        <div>
            <h2>{{ $siswa->nama }}</h2>
            <p>Detail profil, absensi harian, absensi mapel, dan riwayat ketidakhadiran.</p>
        </div>
        <a href="{{ $layout === 'wali' ? '/dashboard/wali/siswa' : '/dashboard/admin/siswa' }}" class="btn">Kembali</a>
    </div>

    <div class="cards">
        <div class="card"><h3>NIS</h3><p>{{ $siswa->nis ?? '-' }}</p></div>
        <div class="card"><h3>Kelas</h3><p>{{ $siswa->nama_kelas ?? '-' }}</p></div>
        <div class="card"><h3>Jurusan</h3><p>{{ $siswa->kode_jurusan ?? '-' }}</p></div>
        <div class="card"><h3>Status</h3><p>{{ ($siswa->aktif ?? true) ? 'Aktif' : 'Nonaktif' }}</p></div>
    </div>

    <div class="panel">
        <h3>Data Siswa</h3>
        <table>
            <tr><th>Nama</th><td>{{ $siswa->nama }}</td></tr>
            <tr><th>Username</th><td>{{ $siswa->username }}</td></tr>
            <tr><th>Nama Orang Tua</th><td>{{ $siswa->nama_ortu ?? '-' }}</td></tr>
            <tr><th>No Orang Tua</th><td>{{ $siswa->no_ortu ?? '-' }}</td></tr>
            <tr><th>Wali Kelas</th><td>{{ $siswa->nama_wali ?? '-' }}</td></tr>
        </table>
    </div>

    <div class="overview">
        <div class="panel">
            <h3>Ringkasan Absensi Harian</h3>
            @foreach($ringkasanHarian as $label => $total)
                <div class="metric-row">
                    <span>{{ ucfirst($label) }}</span>
                    <strong>{{ $total }}</strong>
                </div>
            @endforeach
        </div>
        <div class="panel">
            <h3>Ringkasan Absensi Mapel</h3>
            @foreach($ringkasanMapel as $label => $total)
                <div class="metric-row">
                    <span>{{ ucfirst($label) }}</span>
                    <strong>{{ $total }}</strong>
                </div>
            @endforeach
        </div>
    </div>

    <div class="panel">
        <h3>Riwayat Absensi Harian</h3>
        <table>
            <tr>
                <th>Tanggal</th>
                <th>Masuk</th>
                <th>Pulang</th>
                <th>Status</th>
            </tr>
            @forelse($absensiHarian as $row)
                @php
                    $status = in_array($row->status_masuk, ['izin','sakit','alfa','alpa']) ? $row->status_masuk : ($row->status_masuk ?: 'hadir');
                @endphp
                <tr>
                    <td>{{ $row->tanggal }}</td>
                    <td>{{ $row->jam_masuk ?? '-' }} {{ $row->status_masuk ? '('.$row->status_masuk.')' : '' }}</td>
                    <td>{{ $row->jam_pulang ?? '-' }} {{ $row->status_pulang ? '('.$row->status_pulang.')' : '' }}</td>
                    <td><span class="status-pill muted">{{ ucfirst($status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty-table">Belum ada absensi harian.</td></tr>
            @endforelse
        </table>
    </div>

    <div class="panel">
        <h3>Riwayat Absensi Mapel</h3>
        <table>
            <tr>
                <th>Tanggal</th>
                <th>Mapel</th>
                <th>Guru</th>
                <th>Jam Scan</th>
                <th>Status</th>
            </tr>
            @forelse($absensiMapel as $row)
                <tr>
                    <td>{{ $row->tanggal }}</td>
                    <td>{{ $row->nama_mapel }}</td>
                    <td>{{ $row->nama_guru }}</td>
                    <td>{{ $row->jam_scan ?? '-' }}</td>
                    <td><span class="status-pill muted">{{ ucfirst($row->status ?? '-') }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-table">Belum ada absensi mapel.</td></tr>
            @endforelse
        </table>
    </div>

    <div class="panel">
        <h3>Riwayat Izin, Sakit, Alfa</h3>
        <table>
            <tr>
                <th>Tanggal</th>
                <th>Sumber</th>
                <th>Status</th>
                <th>Keterangan</th>
            </tr>
            @forelse($riwayatKhusus as $row)
                <tr>
                    <td>{{ $row['tanggal'] }}</td>
                    <td>{{ $row['sumber'] }}</td>
                    <td><span class="status-pill muted">{{ ucfirst($row['status']) }}</span></td>
                    <td>{{ $row['keterangan'] }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty-table">Tidak ada riwayat izin, sakit, atau alfa.</td></tr>
            @endforelse
        </table>
    </div>
</main>
</body>
</html>
