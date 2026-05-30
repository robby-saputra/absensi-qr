<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Audit Log</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-admin.css') }}">
</head>
<body>
@include('layouts.sidebar_admin')

<main id="content" class="content">
    <div class="welcome">
        <div>
            <h2>Audit Log</h2>
            <p>Catatan perubahan data penting untuk keamanan dan bukti.</p>
        </div>
        <div>
            <a href="/dashboard/admin" class="btn">Kembali</a>
            <a href="/dashboard/admin/pdf/audit-log" target="_blank" class="btn">PDF Resmi</a>
        </div>
    </div>

    <form method="GET" class="panel admin-form">
        <label>
            Cari
            <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Cari user, aksi, judul, tabel">
        </label>
        <label>
            Aksi
            <select name="aksi">
                <option value="">Semua Aksi</option>
                @foreach($aksiList as $aksi)
                    <option value="{{ $aksi }}" {{ $filters['aksi'] === $aksi ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $aksi)) }}</option>
                @endforeach
            </select>
        </label>
        <button class="btn" type="submit">Tampilkan</button>
    </form>

    <div class="panel">
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>User</th>
                    <th>Aksi</th>
                    <th>Data</th>
                    <th>IP</th>
                    <th>Perubahan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i:s') }}</td>
                        <td>{{ $log->user_name ?? '-' }}<br><small>{{ $log->user_role ?? '-' }}</small></td>
                        <td><span class="status-pill muted">{{ $log->aksi }}</span></td>
                        <td>{{ $log->judul ?? '-' }}<br><small>{{ $log->tabel }} #{{ $log->record_id }}</small></td>
                        <td>{{ $log->ip_address ?? '-' }}</td>
                        <td><a class="btn" href="/dashboard/admin/audit-log/{{ $log->id }}">Lihat Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-table">Belum ada audit log.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination-wrap">
            {{ $logs->links() }}
        </div>
    </div>
</main>
</body>
</html>
