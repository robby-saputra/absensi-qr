<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Kelas</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-kelas-index.css') }}">
</head>
<body>

@include('layouts.sidebar_admin')

<main id="content" class="content">

<div class="container">

    <div class="top">

        <h2>Kelola Kelas</h2>

        <div>

            <a href="/dashboard/admin"
               class="btn">

                Kembali

            </a>

            <a href="/dashboard/admin/kelas/create"
               class="btn">

                Tambah Kelas

            </a>

            <a href="/dashboard/admin/pdf/kelas" target="_blank" class="btn">
                PDF Resmi
            </a>

        </div>

    </div>

    @if(session('success'))

        <div class="success">
            {{ session('success') }}
        </div>

    @endif

    <table>

        <tr>
            <th width="60">No</th>
            <th>Nama Kelas</th>
            <th>Jurusan</th>
            <th>Wali Kelas</th>
            <th width="120">Jumlah Siswa</th>
            <th width="170">Aksi</th>
        </tr>

        @forelse($kelas as $k)

            @php

                $jumlahSiswa = \App\Models\User::where('role', 'siswa')
                    ->where('kelas_id', $k->id)
                    ->count();

                /*
                |--------------------------------------------------------------------------
                | WARNA BADGE JURUSAN
                |--------------------------------------------------------------------------
                */
                $kelasColor = '';

                if($k->kode_jurusan == 'TKJ'){

                    $kelasColor = 'tkj';

                }elseif($k->kode_jurusan == 'DKV'){

                    $kelasColor = 'dkv';

                }elseif($k->kode_jurusan == 'AK'){

                    $kelasColor = 'ak';

                }elseif($k->kode_jurusan == 'MP'){

                    $kelasColor = 'mp';

                }

            @endphp

            <tr>

                <td>
                    {{ $loop->iteration }}
                </td>

                <td>

                    <span class="badge {{ $kelasColor }}">

                        {{ $k->nama_kelas }}

                    </span>

                </td>

                <td class="jurusan">

                    {{ $k->kode_jurusan ?? '-' }}

                </td>

                <td>
                    {{ $k->nama_wali ?? '-' }}
                </td>

                <td>
                    {{ $jumlahSiswa }} Siswa
                </td>

                <td>

                    <div class="aksi">

                        <a href="/dashboard/admin/kelas/edit/{{ $k->id }}"
                           class="btn edit">

                            Edit

                        </a>

                        <a href="/dashboard/admin/kelas/delete/{{ $k->id }}"
                           class="btn hapus"
                           data-confirm="Yakin ingin menghapus kelas?">

                            Hapus

                        </a>

                    </div>

                </td>

            </tr>

        @empty

            <tr>

                <td colspan="6" class="kosong">
                    Data kelas belum tersedia
                </td>

            </tr>

        @endforelse

    </table>

</div>


</main>

</body>
</html>




