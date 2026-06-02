<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Detail Audit Log</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/admin-polish.css') }}?v=20260602-polish">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content admin-polish">
        <div class="polish-hero">
            <div>
                <span class="polish-kicker">Detail Audit</span>
                <h1>Detail Audit Log</h1>
                <p>{{ $log->judul ?? '-' }}</p>
            </div>
            <a class="polish-btn secondary" href="/dashboard/admin/audit-log">Kembali</a>
        </div>

        <section class="polish-panel">
            <table class="polish-table">
                <tr>
                    <th>Waktu</th>
                    <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i:s') }}</td>
                </tr>
                <tr>
                    <th>User</th>
                    <td>{{ $log->user_name ?? '-' }} ({{ $log->user_role ?? '-' }})</td>
                </tr>
                <tr>
                    <th>Aksi</th>
                    <td>{{ $log->aksi }}</td>
                </tr>
                <tr>
                    <th>Data</th>
                    <td>{{ $log->tabel ?? '-' }} #{{ $log->record_id ?? '-' }}</td>
                </tr>
                <tr>
                    <th>IP</th>
                    <td>{{ $log->ip_address ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Perangkat</th>
                    <td>{{ $log->user_agent ?? '-' }}</td>
                </tr>
            </table>
        </section>

        <section class="polish-panel">
            <div class="polish-panel-head">
                <div>
                    <h2>Perubahan Data</h2>
                    <p>Perbandingan nilai sebelum dan sesudah aksi.</p>
                </div><span class="polish-badge">{{ count($keys) }} kolom</span>
            </div>
            <table class="polish-table">
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
                        <td>{{ is_scalar($before) || $before === null ? $before ?? '-' : json_encode($before, JSON_UNESCAPED_UNICODE) }}
                        </td>
                        <td>{{ is_scalar($after) || $after === null ? $after ?? '-' : json_encode($after, JSON_UNESCAPED_UNICODE) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="polish-empty">Tidak ada payload perubahan.</td>
                    </tr>
                @endforelse
            </table>
        </section>
    </main>
</body>

</html>
