<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Siswa</title>

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
            flex-wrap:wrap;
            gap:10px;
            margin-bottom:20px;
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
            background:white;
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

        .pb{
            background:#c0392b;
        }

        .aksi{
            display:flex;
            gap:8px;
        }

        .success{
            color:green;
            margin-bottom:15px;
        }

        .kosong{
            text-align:center;
            color:#777;
            padding:20px;
        }

        .filter-box{
            display:flex;
            gap:10px;
            margin-bottom:20px;
            flex-wrap:wrap;
            align-items:center;
        }

        .filter-box input,
        .filter-box select{
            padding:10px;
            border:1px solid #ccc;
            border-radius:5px;
        }

        .filter-box button{
            background:#273c75;
            color:white;
            border:none;
            padding:10px 16px;
            border-radius:5px;
            cursor:pointer;
        }

        .filter-box button:hover{
            background:#192a56;
        }

        .highlight{
            animation:kedip 1s infinite;
            background:#fff3cd !important;
        }

        @keyframes kedip{

            0%{
                background:#fff3cd;
            }

            50%{
                background:#ffe082;
            }

            100%{
                background:#fff3cd;
            }

        }

        .alert-error{
            background:#e84118;
            color:white;
            padding:12px;
            border-radius:6px;
            margin-bottom:15px;
            text-align:center;
        }
        .btn-reset{

    background:#7f8c8d;
    color:white;

    padding:10px 16px;

    border-radius:5px;

    text-decoration:none;

}

.btn-reset:hover{

    background:#636e72;

}
    </style>
</head>
<body>

<div class="container">

    <div class="top">

        <h2>Kelola Siswa</h2>

        <div>

            <a class="btn"
               href="/dashboard/admin">

                Kembali

            </a>

            <a class="btn"
               href="/dashboard/admin/siswa/create">

                Tambah Siswa

            </a>

            <a class="btn"
               href="/dashboard/admin/siswa/import">

                Import Excel

            </a>

        </div>

    </div>

    <!-- FILTER -->
   <form method="GET"
      action="/dashboard/admin/siswa">

    <div class="filter-box">

        <!-- SEARCH -->
        <input type="text"
               name="search"
               placeholder="Cari nama atau NIS..."
               value="{{ request('search') }}">

        <!-- JURUSAN -->
        <select name="jurusan">

            <option value="">
                Semua Jurusan
            </option>

            <option value="TKJ"
                {{ request('jurusan') == 'TKJ' ? 'selected' : '' }}>
                TKJ
            </option>

            <option value="DKV"
                {{ request('jurusan') == 'DKV' ? 'selected' : '' }}>
                DKV
            </option>

            <option value="AK"
                {{ request('jurusan') == 'AK' ? 'selected' : '' }}>
                AK
            </option>

            <option value="MP"
                {{ request('jurusan') == 'MP' ? 'selected' : '' }}>
                MP
            </option>

            <option value="PB"
                {{ request('jurusan') == 'PB' ? 'selected' : '' }}>
                PB
            </option>

        </select>

        <!-- TINGKAT -->
        <select name="tingkat">

            <option value="">
                Semua Tingkat
            </option>

            <option value="X"
                {{ request('tingkat') == 'X' ? 'selected' : '' }}>
                X
            </option>

            <option value="XI"
                {{ request('tingkat') == 'XI' ? 'selected' : '' }}>
                XI
            </option>

            <option value="XII"
                {{ request('tingkat') == 'XII' ? 'selected' : '' }}>
                XII
            </option>

        </select>

       <button type="submit">
    Cari
</button>

<a href="/dashboard/admin/siswa"
   class="btn-reset">

    Reset

</a>

    </div>

</form>

    @if(session('success'))

        <div class="success">
            {{ session('success') }}
        </div>

    @endif

    <table>

        <tr>
            <th width="60">No</th>
            <th>NIS</th>
            <th>Nama</th>
            <th>Username</th>
            <th>Kelas</th>
            <th>Jurusan</th>
            <th>Wali Kelas</th>
            <th>No Orang Tua</th>
            <th>Status</th>
            <th width="300">Aksi</th>
        </tr>

        @forelse($siswa as $s)

            @php

                $namaKelas = $s->nama_kelas ?? '';
                $kelasColor = '';

                if(str_contains($namaKelas, 'TKJ')){

                    $kelasColor = 'tkj';

                }elseif(str_contains($namaKelas, 'DKV')){

                    $kelasColor = 'dkv';

                }elseif(str_contains($namaKelas, 'AK')){

                    $kelasColor = 'ak';

                }elseif(str_contains($namaKelas, 'MP')){

                    $kelasColor = 'mp';

                }elseif(str_contains($namaKelas, 'PB')){

                    $kelasColor = 'pb';

                }

                $highlight = false;

                if(request('search')){

                    if(str_contains(
                        strtolower($s->nama),
                        strtolower(request('search'))
                    )){

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

                    {{ $s->no_ortu ?? '-' }}

                </td>

                <td>
                    {{ ($s->aktif ?? true) ? 'Aktif' : 'Nonaktif' }}
                </td>

                <!-- AKSI -->
                <td>

                    <div class="aksi">

                        <a class="btn edit"
                           href="/dashboard/admin/siswa/edit/{{ $s->id }}">

                            Edit

                        </a>

                        <a class="btn"
                           href="/dashboard/admin/users/{{ $s->id }}/reset-password">

                            Reset

                        </a>

                        <form method="POST"
                              action="/dashboard/admin/users/{{ $s->id }}/toggle-active"
                              style="display:inline;">
                            @csrf
                            <button class="btn"
                                    type="submit"
                                    style="background:#7f8c8d;">
                                {{ ($s->aktif ?? true) ? 'Nonaktif' : 'Aktif' }}
                            </button>
                        </form>

                        <a class="btn hapus"
                           href="/dashboard/admin/siswa/delete/{{ $s->id }}"
                           onclick="return confirm('Yakin ingin menghapus siswa?')">

                            Hapus

                        </a>

                    </div>

                </td>

            </tr>

        @empty

            <tr>

                <td colspan="10">

                    <div class="alert-error">

                        Data tidak tersedia

                    </div>

                </td>

            </tr>

        @endforelse

    </table>

</div>

</body>
</html>
