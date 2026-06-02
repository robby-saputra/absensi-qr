<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>CRUD Absensi Mapel</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        <div class="rekap-head">
            <div>
                <h1>CRUD Absensi Mapel</h1>
                <p>Kelola absensi per jam pelajaran dari guru mapel. Akses penuh khusus superadmin.</p>
            </div>
            <div>
                <a href="/dashboard/admin" class="btn back">Kembali</a>
                <a href="/dashboard/admin/absensi-mapel/create" class="btn">Tambah Absensi Mapel</a>
                <a href="/dashboard/admin/pdf/absensi-mapel-crud" target="_blank" class="btn">PDF Resmi</a>
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
            <input type="date" name="tanggal" value="{{ $filters['tanggal'] ?? '' }}">
            <select name="kelas_id">
                <option value="">Semua Kelas</option>
                @foreach ($kelas as $k)
                    <option value="{{ $k->id }}" {{ ($filters['kelas_id'] ?? '') == $k->id ? 'selected' : '' }}>
                        {{ $k->nama_kelas }}</option>
                @endforeach
            </select>
            <select name="status">
                <option value="">Semua Status</option>
                @foreach (['hadir', 'telat', 'izin', 'sakit', 'alfa'] as $status)
                    <option value="{{ $status }}"
                        {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                placeholder="Cari siswa, NIS, mapel, guru">
            <button class="btn" type="submit">Tampilkan</button>
            <a href="/dashboard/admin/absensi-mapel" class="btn back">Reset</a>
        </form>

        <table>
            <tr>
                <th>Tanggal</th>
                <th>Siswa</th>
                <th>Kelas</th>
                <th>Mapel</th>
                <th>Guru</th>
                <th>Jam Pelajaran</th>
                <th>Jam Scan</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
            @forelse($data as $row)
                <tr>
                    <td>{{ $row->tanggal }}</td>
                    <td>{{ $row->nama_siswa }}<br><small>{{ $row->nis ?? '-' }}</small></td>
                    <td>{{ $row->nama_kelas ?? '-' }}</td>
                    <td>{{ $row->nama_mapel }}</td>
                    <td>
                        {{ $row->status_guru === 'digantikan' ? $row->guru_pengganti ?? $row->guru_utama : $row->guru_utama }}
                        @if ($row->status_guru === 'digantikan')
                            <br><small>Pengganti dari {{ $row->guru_utama }}</small>
                        @endif
                    </td>
                    <td>{{ ucfirst($row->hari) }} {{ $row->jam_mulai }} - {{ $row->jam_selesai }}</td>
                    <td>{{ $row->jam_scan ?? '-' }}</td>
                    <td><span class="status-pill">{{ $row->status }}</span></td>
                    <td>
                        <a class="btn back" href="/dashboard/admin/absensi-mapel/{{ $row->id }}">View</a>
                        <a class="btn" href="/dashboard/admin/absensi-mapel/edit/{{ $row->id }}">Edit</a>
                        <a class="btn btn-danger confirm-delete"
                            href="/dashboard/admin/absensi-mapel/delete/{{ $row->id }}">Hapus</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty-row">Data absensi mapel belum tersedia.</td>
                </tr>
            @endforelse
        </table>

        {{ $data->links() }}
    </main>
</body>

</html>
