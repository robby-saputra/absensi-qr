<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Audit Log</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>
<body>
@include('layouts.sidebar_admin')

<main id="content" class="content">
    <div class="rekap-head">
        <div>
            <h1>Detail Audit Log</h1>
            <p>{{ $log->judul ?? '-' }}</p>
        </div>
        <a class="btn back" href="/dashboard/admin/audit-log">Kembali</a>
    </div>

    <table>
        <tr><th>Waktu</th><td>{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i:s') }}</td></tr>
        <tr><th>User</th><td>{{ $log->user_name ?? '-' }} ({{ $log->user_role ?? '-' }})</td></tr>
        <tr><th>Aksi</th><td>{{ $log->aksi }}</td></tr>
        <tr><th>Data</th><td>{{ $log->tabel ?? '-' }} #{{ $log->record_id ?? '-' }}</td></tr>
        <tr><th>IP</th><td>{{ $log->ip_address ?? '-' }}</td></tr>
        <tr><th>Perangkat</th><td>{{ $log->user_agent ?? '-' }}</td></tr>
    </table>

    <h2 style="margin:22px 0 12px">Perubahan Data</h2>
    <table>
        <tr>
            <th>Kolom</th>
            <th>Sebelum</th>
            <th>Sesudah</th>
        </tr>
        @forelse($keys as $key)
            @php($before = $dataLama[$key] ?? null)
            @php($after = $dataBaru[$key] ?? null)
            <tr>
                <td>{{ $key }}</td>
                <td>{{ is_scalar($before) || $before === null ? ($before ?? '-') : json_encode($before, JSON_UNESCAPED_UNICODE) }}</td>
                <td>{{ is_scalar($after) || $after === null ? ($after ?? '-') : json_encode($after, JSON_UNESCAPED_UNICODE) }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="empty-row">Tidak ada payload perubahan.</td></tr>
        @endforelse
    </table>
</main>
</body>
</html>
