<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Tahun Ajaran</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-admin.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        @include('layouts.alerts')

        <div class="welcome">
            <div>
                <h2>Tahun Ajaran</h2>
                <p>Kelola periode semester agar rekap absensi tidak tercampur.</p>
            </div>
            <div>
                <a href="/dashboard/admin/tahun-ajaran/create" class="btn">Tambah Tahun Ajaran</a>
                <a href="/dashboard/admin/pdf/tahun-ajaran" target="_blank" class="btn">PDF Resmi</a>
            </div>
        </div>

        <div class="panel">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Semester</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tahunAjaran as $item)
                        <tr>
                            <td>{{ $item->nama }}</td>
                            <td>{{ ucfirst($item->semester) }}</td>
                            <td>{{ \Carbon\Carbon::parse($item->tanggal_mulai)->format('d M Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($item->tanggal_selesai)->format('d M Y') }}</td>
                            <td>
                                <span class="status-pill {{ $item->aktif ? 'success' : 'muted' }}">
                                    {{ $item->aktif ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="action-buttons">
                                @if (!$item->aktif)
                                    <form method="POST"
                                        action="/dashboard/admin/tahun-ajaran/{{ $item->id }}/aktif">
                                        @csrf
                                        <button type="submit" class="btn">Set Aktif</button>
                                    </form>
                                @endif
                                <a href="/dashboard/admin/tahun-ajaran/edit/{{ $item->id }}"
                                    class="btn edit">Edit</a>
                                <a href="/dashboard/admin/tahun-ajaran/delete/{{ $item->id }}" class="btn hapus"
                                    data-confirm="Data akan dipindahkan ke arsip dan masih bisa dipulihkan dari menu Arsip Data.">Hapus</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-table">Belum ada tahun ajaran.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>
