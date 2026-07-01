{{-- File ini menampilkan daftar absensi harian yang dapat dipantau dan dikelola oleh admin. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Absensi Harian</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        <div class="rekap-head">
            <div>
                <h1>CRUD Absensi Harian</h1>
                <p>Kelola absensi harian siswa dari guru piket. Akses penuh khusus superadmin.</p>
            </div>
            <div>
                <a href="/dashboard/admin" class="btn back">Kembali</a>
                <a href="/dashboard/admin/absensi/create" class="btn">Tambah Absensi</a>
                <a href="/dashboard/admin/pdf/absensi-harian-crud" target="_blank" class="btn">PDF Resmi</a>
                <form method="POST" action="/dashboard/admin/absensi/sinkron-rekap" style="display:inline">
                    @csrf
                    <input type="hidden" name="tanggal" value="{{ $filters['tanggal'] ?? '' }}">
                    <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaranId ?? '' }}">
                    <button type="submit" class="btn">Submit ke Rekap</button>
                </form>
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
                @foreach (['hadir', 'telat', 'izin', 'sakit', 'alfa', 'pulang', 'pulang_cepat'] as $status)
                    <option value="{{ $status }}"
                        {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                placeholder="Cari nama atau NIS">
            <button class="btn" type="submit">Tampilkan</button>
            <a href="/dashboard/admin/absensi" class="btn back">Reset</a>
        </form>

        <table>
            <tr>
                <th>Tanggal</th>
                <th>Siswa</th>
                <th>NIS</th>
                <th>Kelas</th>
                <th>Jam Masuk</th>
                <th>Status Masuk</th>
                <th>Jam Pulang</th>
                <th>Status Pulang</th>
                <th>Aksi</th>
            </tr>
            @forelse($data as $row)
                <tr>
                    <td>{{ $row->tanggal }}</td>
                    <td>{{ $row->nama_siswa }}</td>
                    <td>{{ $row->nis ?? '-' }}</td>
                    <td>{{ $row->nama_kelas ?? '-' }}</td>
                    <td>{{ $row->jam_masuk ?? '-' }}</td>
                    <td><span class="status-pill">{{ $row->status_masuk ?? '-' }}</span></td>
                    <td>{{ $row->jam_pulang ?? '-' }}</td>
                    <td><span class="status-pill">{{ $row->status_pulang ?? '-' }}</span></td>
                    <td>
                        <a class="btn back" href="/dashboard/admin/absensi/{{ $row->id }}">View</a>
                        <a class="btn" href="/dashboard/admin/absensi/edit/{{ $row->id }}">Edit</a>
                        <a class="btn btn-danger confirm-delete"
                            href="/dashboard/admin/absensi/delete/{{ $row->id }}">Hapus</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty-row">Data absensi harian belum tersedia.</td>
                </tr>
            @endforelse
        </table>

        {{ $data->links() }}
    </main>
</body>

</html>
