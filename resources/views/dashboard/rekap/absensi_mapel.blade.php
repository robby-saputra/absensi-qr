<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Rekap Absensi Mapel</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content" data-print-title="Rekap Absensi Mapel"
        data-print-date="{{ now()->format('d-m-Y H:i') }}">
        <div class="rekap-head">
            <div>
                <h1>Rekap Absensi Mapel</h1>
                <p>Data absensi dari QR sesi guru mapel, berbeda dari absensi harian guru piket.</p>
            </div>
            <div>
                <a href="/dashboard/admin" class="btn back">Kembali</a>
                <button type="button" class="btn" onclick="printReport('Rekap Absensi Mapel')">Print</button>
                <button type="button" class="btn"
                    onclick="exportTableToExcel('rekap-absensi-mapel', 'Rekap Absensi Mapel')">Excel</button>
                <a class="btn" target="_blank"
                    href="/dashboard/admin/rekap/absensi-mapel-pdf?tanggal={{ $tanggal }}&kelas_id={{ $kelasId }}&tahun_ajaran_id={{ $tahunAjaranId }}">PDF
                    Resmi</a>
                <a class="btn" href="/dashboard/admin/absensi-mapel/create">Tambah Absensi Mapel</a>
            </div>
        </div>

        <form method="GET" class="rekap-filter">
            <select name="tahun_ajaran_id">
                <option value="">Semua Tahun Ajaran</option>
                @foreach ($tahunAjaran as $ta)
                    <option value="{{ $ta->id }}" {{ ($tahunAjaranId ?? '') == $ta->id ? 'selected' : '' }}>
                        {{ $ta->nama }} - {{ ucfirst($ta->semester) }} {{ $ta->aktif ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
            <input type="date" name="tanggal" value="{{ $tanggal }}">
            <select name="kelas_id">
                <option value="">Semua Kelas</option>
                @foreach ($kelas as $k)
                    <option value="{{ $k->id }}" {{ ($kelasId ?? '') == $k->id ? 'selected' : '' }}>
                        {{ $k->nama_kelas }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Tampilkan</button>
            <a href="/dashboard/admin/rekap/absensi-mapel" class="btn back">Reset</a>
        </form>

        <table>
            <tr>
                <th>Tanggal</th>
                <th>Siswa</th>
                <th>Kelas</th>
                <th>Mapel</th>
                <th>Guru Utama</th>
                <th>Guru Pelaksana</th>
                <th>Jam Pelajaran</th>
                <th>Jam Scan</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
            @forelse($data as $row)
                <tr>
                    <td>{{ $row->tanggal }}</td>
                    <td>{{ $row->nama_siswa }}</td>
                    <td>{{ $row->nama_kelas ?? '-' }}</td>
                    <td>{{ $row->nama_mapel }}</td>
                    <td>{{ $row->guru_utama }}</td>
                    <td>
                        @if ($row->status_guru == 'digantikan')
                            {{ $row->guru_pengganti ?? '-' }}
                            <br>
                            <small>Pengganti: {{ $row->alasan_tidak_hadir ?? '-' }}</small>
                        @else
                            {{ $row->guru_utama }}
                        @endif
                    </td>
                    <td>{{ $row->jam_mulai }} - {{ $row->jam_selesai }}</td>
                    <td>{{ $row->jam_scan ?? '-' }}</td>
                    <td><span class="status-pill">{{ $row->status }}</span></td>
                    <td>
                        <a class="btn back" href="/dashboard/admin/absensi-mapel/{{ $row->id }}">Lihat</a>
                        <a class="btn" href="/dashboard/admin/absensi-mapel/edit/{{ $row->id }}">Ubah</a>
                        <a class="btn btn-danger confirm-delete"
                            href="/dashboard/admin/absensi-mapel/delete/{{ $row->id }}">Hapus</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="empty-row">Data tidak tersedia.</td>
                </tr>
            @endforelse
        </table>
    </main>
</body>

</html>
