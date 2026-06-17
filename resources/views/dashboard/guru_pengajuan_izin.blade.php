<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Pengajuan Izin Siswa</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>

<body>
    @include('layouts.sidebar_guru')
    <main id="content" class="content">
        @include('layouts.alerts')

        <div class="rekap-head">
            <div>
                <h1>Pengajuan Izin/Sakit Siswa</h1>
                <p>Data pengajuan siswa dari kelas yang Anda ajar. Hasil verifikasi guru piket/admin tampil di sini.</p>
            </div>
        </div>
        <form method="GET" class="rekap-filter"><input type="date" name="tanggal" value="{{ $tanggal }}"><button
                class="btn">Tampilkan</button></form>
        <table>
            <tr>
                <th>Siswa</th>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Status</th>
                <th>Reviewer</th>
                <th>Catatan</th>
            </tr>
            @forelse($pengajuan as $p)
                <tr>
                    <td>{{ $p->nama_siswa }}<br><small>{{ $p->nama_kelas ?? '-' }}</small></td>
                    <td>{{ $p->tanggal_mulai }} s/d {{ $p->tanggal_selesai }}</td>
                    <td><span class="status-pill">{{ $p->jenis }}</span></td>
                    <td>{{ $p->status }}</td>
                    <td>{{ $p->reviewer ?? '-' }}</td>
                    <td>{{ $p->catatan_review ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty-row">Belum ada pengajuan untuk kelas yang Anda ajar.</td>
                </tr>
            @endforelse
        </table>
    </main>
    <script src="{{ asset('js/app-ui.js') }}"></script>
</body>

</html>
