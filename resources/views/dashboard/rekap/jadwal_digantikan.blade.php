<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekap Jadwal Digantikan</title>
<link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>
<body>
@include('layouts.sidebar_admin')

<main id="content" class="content" data-print-title="Rekap Jadwal Digantikan" data-print-date="{{ now()->format('d-m-Y H:i') }}">
<div class="rekap-head">
    <div>
        <h1>Rekap Jadwal Digantikan</h1>
        <p>Pelajaran yang dialihkan karena guru utama tidak hadir.</p>
    </div>
    <div>
        <a href="/dashboard/admin" class="btn back">Kembali</a>
        <button type="button" class="btn" onclick="printReport('Rekap Jadwal Digantikan')">Print</button>
        <button type="button" class="btn" onclick="exportTableToExcel('rekap-jadwal-digantikan', 'Rekap Jadwal Digantikan')">Excel</button>
        <a class="btn" target="_blank" href="/dashboard/admin/rekap/jadwal-digantikan-pdf?hari={{ $hari }}&alasan={{ $alasan }}">PDF Resmi</a>
    </div>
</div>

<form method="GET" class="rekap-filter">
    <select name="hari">
        <option value="">Semua Hari</option>
        @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $h)
            <option value="{{ $h }}" {{ ($hari ?? '') == $h ? 'selected' : '' }}>{{ $h }}</option>
        @endforeach
    </select>
    <select name="alasan">
        <option value="">Semua Alasan</option>
        @foreach(['izin','sakit','inval'] as $a)
            <option value="{{ $a }}" {{ ($alasan ?? '') == $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>
        @endforeach
    </select>
    <button class="btn" type="submit">Tampilkan</button>
    <a href="/dashboard/admin/rekap/jadwal-digantikan" class="btn back">Reset</a>
</form>

<table>
    <tr>
        <th>Hari</th>
        <th>Jam</th>
        <th>Kelas</th>
        <th>Mapel</th>
        <th>Guru Utama</th>
        <th>Pengganti</th>
        <th>Alasan</th>
    </tr>
    @forelse($data as $row)
        <tr>
            <td>{{ $row->hari }}</td>
            <td>{{ $row->jam_mulai }} - {{ $row->jam_selesai }}</td>
            <td>{{ $row->nama_kelas }}</td>
            <td>{{ $row->nama_mapel }}</td>
            <td>{{ $row->guru_utama }}</td>
            <td>{{ $row->guru_pengganti ?? '-' }}</td>
            <td><span class="status-pill warn">{{ ucfirst($row->alasan_tidak_hadir ?? '-') }}</span></td>
        </tr>
    @empty
        <tr><td colspan="7" class="empty-row">Data tidak tersedia.</td></tr>
    @endforelse
</table>
</main>
</body>
</html>
