<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Arsip Data</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>
<body>
@include('layouts.sidebar_admin')

<main id="content" class="content">
    <div class="rekap-head">
        <div>
            <h1>Arsip Data Terhapus</h1>
            <p>Data yang dihapus superadmin masuk ke sini dulu dan bisa dipulihkan.</p>
        </div>
        <div>
            <a class="btn back" href="/dashboard/admin">Kembali</a>
            <a class="btn" target="_blank" href="/dashboard/admin/pdf/arsip?table={{ $table }}">PDF Resmi</a>
        </div>
    </div>

    <form method="GET" class="rekap-filter">
        <select name="table">
            @foreach($tables as $key => $label)
                <option value="{{ $key }}" {{ $table === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <input type="date" name="tanggal_mulai" value="{{ $filters['tanggalMulai'] ?? '' }}">
        <input type="date" name="tanggal_selesai" value="{{ $filters['tanggalSelesai'] ?? '' }}">
        <input type="text" name="deleted_by" value="{{ $filters['deletedBy'] ?? '' }}" placeholder="Cari penghapus">
        <button class="btn" type="submit">Tampilkan</button>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Ringkasan Data</th>
            <th>Diarsipkan</th>
            <th>Aksi</th>
        </tr>
        @forelse($data as $row)
            <tr>
                <td>#{{ $row->id }}</td>
                <td>
                    @foreach((array) $row as $key => $value)
                        @continue(in_array($key, ['password','remember_token']))
                        <small><strong>{{ $key }}:</strong> {{ is_scalar($value) ? $value : json_encode($value) }}</small><br>
                    @endforeach
                </td>
                <td>{{ $row->deleted_at }}</td>
                <td>
                    <a class="btn back" href="/dashboard/admin/arsip/preview?table={{ $table }}&id={{ $row->id }}">Preview</a>
                    <form method="POST" action="/dashboard/admin/arsip/restore" style="display:inline">
                        @csrf
                        <input type="hidden" name="table" value="{{ $table }}">
                        <input type="hidden" name="id" value="{{ $row->id }}">
                        <button class="btn" type="submit">Restore</button>
                    </form>
                    <form method="POST" action="/dashboard/admin/arsip/force-delete" class="confirm-delete" style="display:inline">
                        @csrf
                        <input type="hidden" name="table" value="{{ $table }}">
                        <input type="hidden" name="id" value="{{ $row->id }}">
                        <button class="btn btn-danger" type="submit">Hapus Permanen</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="empty-row">Belum ada arsip untuk tabel ini.</td></tr>
        @endforelse
    </table>

    <div class="pagination-wrap">{{ $data->links() }}</div>
</main>
</body>
</html>
