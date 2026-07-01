{{-- File ini menampilkan rekap guru piket untuk melihat riwayat tugas piket dan pemantauan kehadiran. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Guru Piket</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content" data-print-title="Rekap Guru Piket"
        data-print-date="{{ now()->format('d-m-Y H:i') }}">
        <div class="rekap-head">
            <div>
                <h1>Rekap Guru Piket</h1>
                <p>Status guru piket utama dan jam tugas.</p>
            </div>
            <div>
                <a href="/dashboard/admin" class="btn back">Kembali</a>
                <button type="button" class="btn" onclick="printReport('Rekap Guru Piket')">Print</button>
                <button type="button" class="btn"
                    onclick="exportTableToExcel('rekap-guru-piket', 'Rekap Guru Piket')">Excel</button>
                <a class="btn" target="_blank"
                    href="/dashboard/admin/rekap/guru-piket-pdf?hari={{ $hari }}&status={{ $status }}">PDF
                    Resmi</a>
            </div>
        </div>

        <form method="GET" class="rekap-filter">
            <select name="hari">
                <option value="">Semua Hari</option>
                @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h)
                    <option value="{{ $h }}" {{ ($hari ?? '') == $h ? 'selected' : '' }}>{{ $h }}
                    </option>
                @endforeach
            </select>
            <select name="status">
                <option value="">Semua Status</option>
                @foreach (['Akan Bertugas', 'Sedang Bertugas', 'Izin', 'Sakit', 'Selesai'] as $s)
                    <option value="{{ $s }}" {{ ($status ?? '') == $s ? 'selected' : '' }}>
                        {{ $s }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Tampilkan</button>
            <a href="/dashboard/admin/rekap/guru-piket" class="btn back">Reset</a>
        </form>

        <table>
            <tr>
                <th>Guru Piket</th>
                <th>Hari</th>
                <th>Jam</th>
                <th>Status</th>
                <th>Aktif</th>
            </tr>
            @forelse($data as $row)
                <tr>
                    <td>{{ $row->guru_utama }}</td>
                    <td>{{ ucfirst($row->hari) }}</td>
                    <td>{{ $row->jam_mulai }} - {{ $row->jam_selesai }}</td>
                    <td>
                        <span class="status-pill {{ in_array($row->status, ['Izin', 'Sakit']) ? 'danger' : '' }}">
                            {{ $row->status }}
                        </span>
                    </td>
                    <td>{{ $row->aktif ? 'Ya' : 'Tidak' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty-row">Data tidak tersedia.</td>
                </tr>
            @endforelse
        </table>
    </main>
</body>

</html>
