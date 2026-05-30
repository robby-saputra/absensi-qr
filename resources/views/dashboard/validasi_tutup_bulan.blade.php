<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Validasi Tutup Bulan</title>
<link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>
<body>
@if(($user->role ?? '') === 'admin')
    @include('layouts.sidebar_admin')
@else
    @include('layouts.sidebar_wali')
@endif
<main id="content" class="content">
    <div class="rekap-head">
        <div><h1>Validasi Tutup Bulan</h1><p>Periksa data belum lengkap sebelum laporan bulan {{ $bulan }} dikunci.</p></div>
        <a class="btn back" href="{{ ($user->role ?? '') === 'admin' ? '/dashboard/admin' : '/dashboard/wali' }}">Kembali</a>
    </div>
    <form method="GET" class="rekap-filter">
        <label>Bulan <input type="month" name="bulan" value="{{ $bulan }}"></label>
        <label>Tahun Ajaran
            <select name="tahun_ajaran_id">
                @foreach($tahunAjaran as $ta)
                    <option value="{{ $ta->id }}" {{ (string)$tahunAjaranId === (string)$ta->id ? 'selected' : '' }}>{{ $ta->nama }} - {{ ucfirst($ta->semester) }}</option>
                @endforeach
            </select>
        </label>
        @if(($user->role ?? '') === 'admin')
            <label>Kelas
                <select name="kelas_id">
                    <option value="">Semua Kelas</option>
                    @foreach($kelas as $k)<option value="{{ $k->id }}" {{ (string)$kelasId === (string)$k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>@endforeach
                </select>
            </label>
        @endif
        <button class="btn">Cek Data</button>
    </form>

    <div class="cards">
        <div class="card"><h3>Belum Pulang</h3><p>{{ $hasil['belumPulang']->count() }}</p></div>
        <div class="card"><h3>Belum Absen Mapel</h3><p>{{ $hasil['belumMapel']->count() }}</p></div>
        <div class="card"><h3>Alfa</h3><p>{{ $hasil['alfaBelumDiproses']->count() }}</p></div>
        <div class="card"><h3>Izin Menunggu</h3><p>{{ $hasil['izinBelumReview']->count() }}</p></div>
    </div>

    @foreach([
        'belumPulang' => ['Siswa Belum Absen Pulang', ['Tanggal','Nama','Kelas','Jam Masuk']],
        'belumMapel' => ['Siswa Belum Absen Mapel', ['Hari','Nama','Kelas','Mapel','Jam']],
        'alfaBelumDiproses' => ['Data Alfa', ['Tanggal','Nama','Kelas','Status Masuk','Status Pulang']],
        'izinBelumReview' => ['Pengajuan Izin/Sakit Belum Direview', ['Tanggal','Nama','Kelas','Jenis']]
    ] as $key => [$judul, $headers])
        <div class="table-wrap">
            <table>
                <tr><th colspan="{{ count($headers) }}">{{ $judul }}</th></tr>
                <tr>@foreach($headers as $h)<th>{{ $h }}</th>@endforeach</tr>
                @forelse($hasil[$key] as $row)
                    <tr>
                        @if($key === 'belumPulang')
                            <td>{{ $row->tanggal }}</td><td>{{ $row->nama }}</td><td>{{ $row->nama_kelas ?? '-' }}</td><td>{{ $row->jam_masuk }}</td>
                        @elseif($key === 'belumMapel')
                            <td>{{ $row->hari }}</td><td>{{ $row->nama }}</td><td>{{ $row->nama_kelas ?? '-' }}</td><td>{{ $row->nama_mapel ?? '-' }}</td><td>{{ $row->jam_mulai }} - {{ $row->jam_selesai }}</td>
                        @elseif($key === 'alfaBelumDiproses')
                            <td>{{ $row->tanggal }}</td><td>{{ $row->nama }}</td><td>{{ $row->nama_kelas ?? '-' }}</td><td>{{ $row->status_masuk ?? '-' }}</td><td>{{ $row->status_pulang ?? '-' }}</td>
                        @else
                            <td>{{ $row->tanggal_mulai }} s/d {{ $row->tanggal_selesai }}</td><td>{{ $row->nama }}</td><td>{{ $row->nama_kelas ?? '-' }}</td><td>{{ ucfirst($row->jenis) }}</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ count($headers) }}" class="empty-row">Aman, tidak ada data bermasalah.</td></tr>
                @endforelse
            </table>
        </div>
    @endforeach
</main>
</body>
</html>
