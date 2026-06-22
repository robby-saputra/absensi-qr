<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Guru</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-guru-index.css') }}">
</head>

<body>

    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        @include('layouts.alerts')

        <h2>Kelola Guru</h2>

        <p>
            <a class="btn" href="/dashboard/admin">Kembali</a>
            <a class="btn" href="/dashboard/admin/guru/create">Tambah Guru</a>
            <a class="btn" target="_blank" href="/dashboard/admin/pdf/guru">PDF Resmi</a>
        </p>

        <form class="filter-box" method="GET" action="/dashboard/admin/guru">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                placeholder="Cari nama, NUPTK, username">

            <select name="status">
                <option value="">Semua Status</option>
                <option value="aktif" {{ ($filters['status'] ?? '') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                <option value="nonaktif" {{ ($filters['status'] ?? '') === 'nonaktif' ? 'selected' : '' }}>Nonaktif
                </option>
            </select>

            <select name="tugas">
                <option value="">Semua Tugas</option>
                <option value="mapel" {{ ($filters['tugas'] ?? '') === 'mapel' ? 'selected' : '' }}>Guru Mapel
                </option>
                <option value="wali" {{ ($filters['tugas'] ?? '') === 'wali' ? 'selected' : '' }}>Wali Kelas
                </option>
                <option value="piket" {{ ($filters['tugas'] ?? '') === 'piket' ? 'selected' : '' }}>Guru Piket
                </option>
                <option value="tanpa_tugas" {{ ($filters['tugas'] ?? '') === 'tanpa_tugas' ? 'selected' : '' }}>
                    Belum Ada Tugas</option>
            </select>

            <button type="submit" class="btn">Cari</button>
            <a class="btn btn-muted" href="/dashboard/admin/guru">Reset</a>
        </form>

        <div class="result-info">
            Menampilkan {{ $guru->count() }} data guru
            @if (($filters['q'] ?? '') || ($filters['status'] ?? '') || ($filters['tugas'] ?? ''))
                sesuai filter.
            @endif
        </div>

        <table>
            <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>NUPTK</th>
                <th>Username</th>
                <th>Role</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>

            @foreach ($guru as $g)
                <tr>
                    <td>{{ $g->id }}</td>
                    <td>{{ $g->nama }}</td>
                    <td>{{ $g->nuptk }}</td>
                    <td>{{ $g->username }}</td>
                    <td>{{ $g->role }}</td>
                    <td>{{ $g->aktif ?? true ? 'Aktif' : 'Nonaktif' }}</td>
                    <td>
                        <div class="aksi">
                            <a class="btn edit" href="/dashboard/admin/guru/edit/{{ $g->id }}">Edit</a>
                            <a class="btn" href="/dashboard/admin/users/{{ $g->id }}/reset-password">Reset
                                Password</a>
                            <form class="inline" method="POST"
                                action="/dashboard/admin/users/{{ $g->id }}/toggle-active">
                                @csrf
                                <button class="btn btn-muted" type="submit">
                                    {{ $g->aktif ?? true ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            <a class="btn hapus" href="/dashboard/admin/guru/delete/{{ $g->id }}">Hapus</a>
                        </div>
                    </td>
                </tr>
            @endforeach

            @if ($guru->isEmpty())
                <tr>
                    <td colspan="7" class="kosong">Tidak ada data guru yang sesuai filter.</td>
                </tr>
            @endif

        </table>

    </main>

</body>

</html>
