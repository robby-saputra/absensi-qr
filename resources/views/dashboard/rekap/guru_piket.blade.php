<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekap Guru Piket</title>
<link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>
<body>
@include('layouts.sidebar_admin')

<main id="content" class="content">
<div class="rekap-head">
    <div>
        <h1>Rekap Guru Piket</h1>
        <p>Status guru piket, guru pengganti, dan jam tugas.</p>
    </div>
    <a href="/dashboard/admin" class="btn back">Kembali</a>
</div>

<form method="GET" class="rekap-filter">
    <select name="hari">
        <option value="">Semua Hari</option>
        @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $h)
            <option value="{{ $h }}" {{ ($hari ?? '') == $h ? 'selected' : '' }}>{{ $h }}</option>
        @endforeach
    </select>
    <select name="status">
        <option value="">Semua Status</option>
        @foreach(['Akan Bertugas','Sedang Bertugas','Izin','Sakit','Digantikan','Selesai'] as $s)
            <option value="{{ $s }}" {{ ($status ?? '') == $s ? 'selected' : '' }}>{{ $s }}</option>
        @endforeach
    </select>
    <button class="btn" type="submit">Tampilkan</button>
    <a href="/dashboard/admin/rekap/guru-piket" class="btn back">Reset</a>
</form>

<table>
    <tr>
        <th>Guru Piket</th>
        <th>Pengganti 1</th>
        <th>Pengganti 2</th>
        <th>Hari</th>
        <th>Jam</th>
        <th>Status</th>
        <th>Aktif</th>
    </tr>
    @forelse($data as $row)
        <tr>
            <td>{{ $row->guru_utama }}</td>
            <td>{{ $row->guru_pengganti ?? '-' }}</td>
            <td>{{ $row->guru_pengganti2 ?? '-' }}</td>
            <td>{{ ucfirst($row->hari) }}</td>
            <td>{{ $row->jam_mulai }} - {{ $row->jam_selesai }}</td>
            <td>
                <span class="status-pill {{ in_array($row->status, ['Izin','Sakit']) ? 'danger' : '' }}">
                    {{ $row->status }}
                </span>
            </td>
            <td>{{ $row->aktif ? 'Ya' : 'Tidak' }}</td>
        </tr>
    @empty
        <tr><td colspan="7" class="empty-row">Data tidak tersedia.</td></tr>
    @endforelse
</table>
</main>
</body>
</html>
