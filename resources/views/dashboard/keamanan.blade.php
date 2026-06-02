<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Dashboard Keamanan</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')
    <main id="content" class="content">
        <div class="rekap-head">
            <div>
                <h1>Dashboard Keamanan</h1>
                <p>Monitoring user online, gagal login, akun nonaktif, dan aktivitas superadmin.</p>
            </div>
            <div><a class="btn back" href="/dashboard/admin">Kembali</a><a class="btn" target="_blank"
                    href="/dashboard/admin/pdf/keamanan">PDF Resmi</a></div>
        </div>
        <div class="rekap-filter">
            <span class="status-pill">Online: {{ $stats['online'] }}</span>
            <span class="status-pill">Gagal login hari ini: {{ $stats['failed_today'] }}</span>
            <span class="status-pill">Mencurigakan: {{ $stats['suspicious_today'] }}</span>
            <span class="status-pill">Akun nonaktif: {{ $stats['inactive'] }}</span>
        </div>
        <h2>User Online</h2>
        <table>
            <tr>
                <th>Nama</th>
                <th>Role</th>
                <th>Login</th>
                <th>Terakhir Aktif</th>
                <th>IP</th>
            </tr>
            @forelse($online as $row)
                <tr>
                    <td>{{ $row->nama }}<br><small>{{ $row->username }}</small></td>
                    <td>{{ $row->role }}</td>
                    <td>{{ $row->login_at ?? '-' }}</td>
                    <td>{{ $row->last_seen_at ?? '-' }}</td>
                    <td>{{ $row->ip_address ?? '-' }}</td>
            </tr>@empty
                <tr>
                    <td colspan="5" class="empty-row">Belum ada user online.</td>
                </tr>
            @endforelse
        </table>
        <h2 style="margin-top:22px">Event Login</h2>
        <table>
            <tr>
                <th>Waktu</th>
                <th>Username</th>
                <th>Event</th>
                <th>Percobaan</th>
                <th>IP</th>
                <th>Keterangan</th>
            </tr>
            @forelse($events as $e)
                <tr>
                    <td>{{ $e->created_at }}</td>
                    <td>{{ $e->username ?? '-' }}</td>
                    <td><span class="status-pill">{{ $e->event_type }}</span></td>
                    <td>{{ $e->attempt_count }}</td>
                    <td>{{ $e->ip_address ?? '-' }}</td>
                    <td>{{ $e->keterangan ?? '-' }}</td>
            </tr>@empty
                <tr>
                    <td colspan="6" class="empty-row">Belum ada event login.</td>
                </tr>
            @endforelse
        </table>
        <h2 style="margin-top:22px">Akun Nonaktif</h2>
        <table>
            <tr>
                <th>Nama</th>
                <th>Username</th>
                <th>Role</th>
            </tr>
            @forelse($inactive as $u)
                <tr>
                    <td>{{ $u->nama }}</td>
                    <td>{{ $u->username }}</td>
                    <td>{{ $u->role }}</td>
            </tr>@empty
                <tr>
                    <td colspan="3" class="empty-row">Tidak ada akun nonaktif.</td>
                </tr>
            @endforelse
        </table>
        <h2 style="margin-top:22px">Aktivitas Superadmin</h2>
        <table>
            <tr>
                <th>Waktu</th>
                <th>Aksi</th>
                <th>Data</th>
                <th>IP</th>
            </tr>
            @forelse($superadminLogs as $log)
                <tr>
                    <td>{{ $log->created_at }}</td>
                    <td>{{ $log->aksi }}</td>
                    <td>{{ $log->judul }}<br><small>{{ $log->tabel }} #{{ $log->record_id }}</small></td>
                    <td>{{ $log->ip_address ?? '-' }}</td>
            </tr>@empty
                <tr>
                    <td colspan="4" class="empty-row">Belum ada aktivitas superadmin.</td>
                </tr>
            @endforelse
        </table>
    </main>
</body>

</html>
