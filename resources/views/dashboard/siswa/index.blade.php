{{-- File ini menampilkan daftar siswa sebagai data master utama untuk absensi, kelas, dan laporan sekolah. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Siswa</title>
</head>

<body>

    @include('layouts.sidebar_admin')
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-siswa-index.css') }}?v={{ filemtime(public_path('css/pages/dashboard-siswa-index.css')) }}">

    <main id="content" class="content">

        <div class="container">
            @include('layouts.alerts')

            <div class="top">

                <h2>Kelola Siswa</h2>

                <div>

                    <a class="btn" href="/dashboard/admin">

                        Kembali

                    </a>

                    <a class="btn" href="/dashboard/admin/siswa/create">

                        Tambah Siswa

                    </a>

                    <a class="btn" target="_blank" href="/dashboard/admin/pdf/siswa">
                        PDF Resmi
                    </a>

                    <a class="btn btn-template" href="/dashboard/admin/siswa/template">
                        Download Template
                    </a>

                    <a class="btn btn-upload" href="/dashboard/admin/siswa/import">
                        Upload Template
                    </a>

                    <a class="btn btn-muted" href="/dashboard/admin/siswa?status=nonaktif">
                        Siswa Nonaktif ({{ $ringkasanStatus['nonaktif'] ?? 0 }})
                    </a>

                </div>

            </div>

            <section class="template-panel">
                <div class="template-copy">
                    <span>Template Data Siswa</span>
                    <h3>Import siswa lebih cepat dari Excel</h3>
                    <p>Unduh template resmi, isi data siswa sesuai kolom yang disediakan, lalu upload kembali untuk menambahkan banyak siswa sekaligus.</p>
                </div>
                <div class="template-actions">
                    <a href="/dashboard/admin/siswa/template" class="template-card download">
                        <strong>Download Template</strong>
                        <small>Kolom: nis, nama, username, password, kelas, nama_ortu, no_ortu, status.</small>
                    </a>
                    <a href="/dashboard/admin/siswa/import" class="template-card upload">
                        <strong>Upload Template</strong>
                        <small>Unggah file Excel atau CSV yang sudah diisi sesuai template.</small>
                    </a>
                </div>
            </section>

            <!-- FILTER -->
            <form method="GET" action="/dashboard/admin/siswa">

                <div class="filter-box">

                    <!-- SEARCH -->
                    <input type="text" name="search" placeholder="Cari nama, NIS, orang tua, atau no orang tua..."
                        value="{{ request('search') }}">

                    <!-- JURUSAN -->
                    <select name="jurusan">

                        <option value="">
                            Semua Jurusan
                        </option>

                        <option value="TKJ" {{ request('jurusan') == 'TKJ' ? 'selected' : '' }}>
                            TKJ
                        </option>

                        <option value="DKV" {{ request('jurusan') == 'DKV' ? 'selected' : '' }}>
                            DKV
                        </option>

                        <option value="AK" {{ request('jurusan') == 'AK' ? 'selected' : '' }}>
                            AK
                        </option>

                        <option value="MP" {{ request('jurusan') == 'MP' ? 'selected' : '' }}>
                            MP
                        </option>

                        <option value="PB" {{ request('jurusan') == 'PB' ? 'selected' : '' }}>
                            PB
                        </option>

                    </select>

                    <!-- TINGKAT -->
                    <select name="tingkat">

                        <option value="">
                            Semua Tingkat
                        </option>

                        <option value="X" {{ request('tingkat') == 'X' ? 'selected' : '' }}>
                            X
                        </option>

                        <option value="XI" {{ request('tingkat') == 'XI' ? 'selected' : '' }}>
                            XI
                        </option>

                        <option value="XII" {{ request('tingkat') == 'XII' ? 'selected' : '' }}>
                            XII
                        </option>

                    </select>

                    <select name="status">
                        <option value="aktif" {{ ($status ?? request('status', 'aktif')) == 'aktif' ? 'selected' : '' }}>
                            Siswa Aktif
                        </option>
                        <option value="nonaktif" {{ ($status ?? request('status')) == 'nonaktif' ? 'selected' : '' }}>
                            Siswa Nonaktif
                        </option>
                        <option value="semua" {{ ($status ?? request('status')) == 'semua' ? 'selected' : '' }}>
                            Semua Status
                        </option>
                    </select>

                    <button type="submit">
                        Cari
                    </button>

                    <a href="/dashboard/admin/siswa" class="btn-reset">

                        Reset

                    </a>

                </div>

            </form>

            <table>

                <tr>
                    <th width="60">No</th>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Kelas</th>
                    <th>Jurusan</th>
                    <th>Wali Kelas</th>
                    <th>Nama Orang Tua</th>
                    <th>No Orang Tua</th>
                    <th>Status</th>
                    <th width="300">Aksi</th>
                </tr>

                @forelse($siswa as $s)
                    @php

                        $namaKelas = $s->nama_kelas ?? '';
                        $kelasColor = '';

                        if (str_contains($namaKelas, 'TKJ')) {
                            $kelasColor = 'tkj';
                        } elseif (str_contains($namaKelas, 'DKV')) {
                            $kelasColor = 'dkv';
                        } elseif (str_contains($namaKelas, 'AK')) {
                            $kelasColor = 'ak';
                        } elseif (str_contains($namaKelas, 'MP')) {
                            $kelasColor = 'mp';
                        } elseif (str_contains($namaKelas, 'PB')) {
                            $kelasColor = 'pb';
                        }

                        $highlight = false;

                        if (request('search')) {
                            if (str_contains(strtolower($s->nama), strtolower(request('search')))) {
                                $highlight = true;
                            }
                        }

                    @endphp

                    <tr class="{{ $highlight ? 'highlight' : '' }}">

                        <td>
                            {{ $loop->iteration }}
                        </td>

                        <!-- NIS -->
                        <td>

                            {{ $s->nis ?? '-' }}

                        </td>

                        <!-- NAMA -->
                        <td>
                            {{ $s->nama }}
                        </td>

                        <!-- USERNAME -->
                        <td>
                            {{ $s->username }}
                        </td>

                        <!-- KELAS -->
                        <td>

                            <span class="badge {{ $kelasColor }}">

                                {{ $s->nama_kelas ?? '-' }}

                            </span>

                        </td>

                        <!-- JURUSAN -->
                        <td>

                            {{ $s->kode_jurusan ?? '-' }}

                        </td>

                        <!-- WALI -->
                        <td>

                            {{ $s->nama_wali ?? '-' }}

                        </td>

                        <!-- ORTU -->
                        <td>

                            {{ $s->nama_ortu ?? '-' }}

                        </td>

                        <td>

                            {{ $s->no_ortu ?? '-' }}

                        </td>

                        <td>
                            {{ $s->aktif ?? true ? 'Aktif' : 'Nonaktif' }}
                        </td>

                        <!-- AKSI -->
                        <td>

                            <div class="aksi">

                                <a class="btn edit" href="/dashboard/admin/siswa/edit/{{ $s->id }}">

                                    Edit

                                </a>

                                <a class="btn" href="/dashboard/admin/siswa/detail/{{ $s->id }}">

                                    View

                                </a>

                                <a class="btn" href="/dashboard/admin/users/{{ $s->id }}/reset-password">

                                    Reset

                                </a>

                                <form method="POST" action="/dashboard/admin/users/{{ $s->id }}/toggle-active"
                                    class="inline-form">
                                    @csrf
                                    <button class="btn btn-muted" type="submit">
                                        {{ $s->aktif ?? true ? 'Nonaktif' : 'Aktif' }}
                                    </button>
                                </form>

                                <a class="btn hapus" href="/dashboard/admin/siswa/delete/{{ $s->id }}"
                                    data-confirm="Data akan dihapus dari daftar utama.">

                                    Hapus

                                </a>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="11">

                            <div class="alert-error">

                                Data tidak tersedia

                            </div>

                        </td>

                    </tr>
                @endforelse

            </table>

        </div>


    </main>

</body>

</html>
