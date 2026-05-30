<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Backup & Restore Database</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>
<body>
@include('layouts.sidebar_admin')

<main id="content" class="content">
    <div class="rekap-head">
        <div>
            <h1>Backup & Restore Database</h1>
            <p>Pengaman superadmin sebelum import Excel, edit besar, atau perbaikan data.</p>
        </div>
        <div>
            <a class="btn back" href="/dashboard/admin">Kembali</a>
            <form method="POST" action="/dashboard/admin/backup/create" style="display:inline">
                @csrf
                <button class="btn" type="submit">Backup JSON</button>
            </form>
            <form method="POST" action="/dashboard/admin/backup/create-sql" style="display:inline">
                @csrf
                <button class="btn" type="submit">Backup SQL</button>
            </form>
        </div>
    </div>

    <div class="info-box" style="margin-bottom:16px">
        JSON dipakai untuk restore internal sistem. SQL bisa dipakai untuk arsip manual/phpMyAdmin dan juga bisa direstore dari halaman ini.
    </div>

    <table>
        <tr>
            <th>File</th>
            <th>Tipe</th>
            <th>Dibuat</th>
            <th>Ukuran</th>
            <th>Aksi</th>
        </tr>
        @forelse($backups as $backup)
            <tr>
                <td>{{ $backup->name }}</td>
                <td><span class="status-pill">{{ $backup->type }}</span></td>
                <td>{{ $backup->created_at }}</td>
                <td>{{ number_format($backup->size / 1024, 1) }} KB</td>
                <td>
                    <a class="btn back" href="/dashboard/admin/backup/download/{{ $backup->name }}">Download</a>
                    <form method="POST" action="/dashboard/admin/backup/restore/{{ $backup->name }}" class="inline confirm-restore" style="display:inline">
                        @csrf
                        <button class="btn btn-danger" type="submit">Restore</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="empty-row">Belum ada file backup.</td></tr>
        @endforelse
    </table>
</main>
</body>
</html>
