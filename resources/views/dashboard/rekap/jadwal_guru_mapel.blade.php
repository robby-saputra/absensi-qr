{{-- File ini menampilkan rekap jadwal guru mata pelajaran sebagai laporan pembagian jadwal mengajar. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Jadwal Guru Mapel</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content" data-print-title="Rekap Jadwal Guru Mapel"
        data-print-date="{{ now()->format('d-m-Y H:i') }}">
        <div class="rekap-head">
            <div>
                <h1>Rekap Jadwal Guru Mapel</h1>
                <p>Daftar jam mengajar guru per mata pelajaran dan kelas.</p>
            </div>
            <div>
                <a href="/dashboard/admin" class="btn back">Kembali</a>
                <button type="button" class="btn" onclick="printReport('Rekap Jadwal Guru Mapel')">Print</button>
                <button type="button" class="btn"
                    onclick="exportTableToExcel('rekap-jadwal-guru-mapel', 'Rekap Jadwal Guru Mapel')">Excel</button>
                <a class="btn" target="_blank"
                    href="/dashboard/admin/rekap/jadwal-guru-mapel-pdf?guru_id={{ $guruId }}&hari={{ $hari }}">PDF
                    Resmi</a>
            </div>
        </div>

        <form method="GET" class="rekap-filter">
            <select name="guru_id">
                <option value="">Semua Guru</option>
                @foreach ($guru as $g)
                    <option value="{{ $g->id }}" {{ ($guruId ?? '') == $g->id ? 'selected' : '' }}>
                        {{ $g->nama }}</option>
                @endforeach
            </select>
            <select name="hari">
                <option value="">Semua Hari</option>
                @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h)
                    <option value="{{ $h }}" {{ ($hari ?? '') == $h ? 'selected' : '' }}>{{ $h }}
                    </option>
                @endforeach
            </select>
            <button class="btn" type="submit">Tampilkan</button>
            <a href="/dashboard/admin/rekap/jadwal-guru-mapel" class="btn back">Reset</a>
        </form>

        <table>
            <tr>
                <th>Guru</th>
                <th>Hari</th>
                <th>Jam</th>
                <th>Kelas</th>
                <th>Mapel</th>
                <th>Status</th>
            </tr>
            @forelse($data as $row)
                <tr>
                    <td>{{ $row->guru_utama }}</td>
                    <td>{{ $row->hari }}</td>
                    <td>{{ $row->jam_mulai }} - {{ $row->jam_selesai }}</td>
                    <td>{{ $row->nama_kelas }}</td>
                    <td>{{ $row->nama_mapel }}</td>
                    <td>
                        <span class="status-pill">
                            {{ $row->status_guru ?? 'belum dipilih' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty-row">Data tidak tersedia.</td>
                </tr>
            @endforelse
        </table>
    </main>
</body>

</html>
