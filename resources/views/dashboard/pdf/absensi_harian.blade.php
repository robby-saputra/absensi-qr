<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>{{ $title }}</title>
<style>
body{font-family:Arial,sans-serif;color:#111827;margin:28px}
.kop{display:flex;align-items:center;gap:16px;border-bottom:3px solid #111827;padding-bottom:14px;margin-bottom:18px}
.kop img{width:76px;height:76px;object-fit:contain}
.kop h1{font-size:22px;margin:0;text-transform:uppercase;letter-spacing:0}
.kop p{margin:4px 0 0;color:#374151}
.meta{display:flex;justify-content:space-between;margin:14px 0 16px;font-size:13px}
table{border-collapse:collapse;width:100%;font-size:12px}
th{background:#1f2937;color:white}
th,td{border:1px solid #6b7280;padding:7px;text-align:left}
.ttd{display:flex;justify-content:flex-end;margin-top:38px}.ttd div{text-align:center;min-width:220px}
@media print{button{display:none}body{margin:16px}}
</style>
</head>
<body>
<button onclick="window.print()">Cetak / Save as PDF</button>
<div class="kop">
    <img src="{{ asset(\App\Services\AttendanceSettingService::logoSekolah()) }}" alt="Logo">
    <div>
        <h1>{{ \App\Services\AttendanceSettingService::namaSekolah() }}</h1>
        <p>Dokumen Resmi Rekap Absensi Harian</p>
    </div>
</div>
<div class="meta">
    <div>Periode: {{ ($filters['mode'] ?? '') === 'bulan' ? ($filters['bulan'] ?? '-') : ($filters['tanggal'] ?? '-') }}</div>
    <div>Dicetak: {{ now()->format('d-m-Y H:i') }}</div>
</div>
<table>
<tr><th>No</th><th>Tanggal</th><th>Nama</th><th>NIS</th><th>Kelas</th><th>Masuk</th><th>Status Masuk</th><th>Pulang</th><th>Status Pulang</th></tr>
@foreach($data as $i => $row)
<tr><td>{{ $i+1 }}</td><td>{{ $row->tanggal }}</td><td>{{ $row->nama }}</td><td>{{ $row->nis }}</td><td>{{ $row->nama_kelas }}</td><td>{{ $row->jam_masuk ?? '-' }}</td><td>{{ $row->status_masuk ?? '-' }}</td><td>{{ $row->jam_pulang ?? '-' }}</td><td>{{ $row->status_pulang ?? '-' }}</td></tr>
@endforeach
</table>
<div class="ttd"><div><p>Mengetahui,</p><br><br><br><strong>Superadmin / Petugas</strong></div></div>
</body>
</html>
