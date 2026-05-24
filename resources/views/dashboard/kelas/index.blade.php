<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Kelas</title>

    <style>
        body{
            font-family:Arial, sans-serif;
            background:#f5f6fa;
            padding:30px;
        }

        .container{
            background:white;
            padding:20px;
            border-radius:10px;
            box-shadow:0 2px 8px rgba(0,0,0,0.08);
        }

        h2{
            margin-top:0;
            margin-bottom:20px;
        }

        .top{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:20px;
            flex-wrap:wrap;
            gap:10px;
        }

        .btn{
            padding:8px 14px;
            background:#273c75;
            color:white;
            text-decoration:none;
            border-radius:5px;
            border:none;
            cursor:pointer;
        }

        .btn:hover{
            background:#192a56;
        }

        .edit{
            background:#00a8ff;
        }

        .edit:hover{
            background:#0097e6;
        }

        .hapus{
            background:#e84118;
        }

        .hapus:hover{
            background:#c23616;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        th, td{
            border:1px solid #ddd;
            padding:12px;
            text-align:left;
        }

        th{
            background:#273c75;
            color:white;
        }

        .badge{
            color:white;
            padding:5px 10px;
            border-radius:5px;
            font-size:13px;
            font-weight:bold;
        }

        .tkj{
            background:#3498db;
        }

        .dkv{
            background:#27ae60;
        }

        .ak{
            background:#e67e22;
        }

        .mp{
            background:#8e44ad;
        }

        .kosong{
            text-align:center;
            color:#777;
            padding:20px;
        }

        .aksi{
            display:flex;
            gap:8px;
        }

        .success{
            color:green;
            margin-bottom:15px;
        }

        .jurusan{
            font-weight:bold;
        }
    </style>
</head>
<body>

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
                           onclick="return confirm('Yakin ingin menghapus kelas?')">

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

</body>
</html>