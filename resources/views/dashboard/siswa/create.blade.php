<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Siswa</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-siswa-create.css') }}">
</head>
<body>

@include('layouts.sidebar_admin')

<main id="content" class="content">

<div class="box">

    <h2>Tambah Siswa</h2>

    @if ($errors->any())

        <div class="error">

            <ul class="form-errors">

                @foreach ($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif

    <form method="POST"
          action="/dashboard/admin/siswa/store">

        @csrf

        <!-- NAMA -->
        <label>Nama Siswa</label>

        <input type="text"
               name="nama"
               placeholder="Masukkan nama siswa">

        <!-- NIS -->
        <label>NIS</label>

        <input type="text"
               name="nis"
               placeholder="Masukkan NIS">

        <!-- USERNAME -->
        <label>Username</label>

        <input type="text"
               name="username"
               placeholder="Masukkan username">

        <!-- PASSWORD -->
        <label>Password</label>

        <input type="password"
               name="password"
               placeholder="Masukkan password">

        <!-- JURUSAN -->
        <label>Jurusan</label>

        <select id="jurusan">

            <option value="">
                -- Pilih Jurusan --
            </option>

            @foreach($jurusan as $j)

                <option value="{{ $j->id }}">

                    {{ $j->kode_jurusan }}
                    -
                    {{ $j->nama_jurusan }}

                </option>

            @endforeach

        </select>

        <!-- KELAS -->
        <label>Kelas</label>

        <select name="kelas_id"
                id="kelas_id"
                required>

            <option value="">
                -- Pilih Kelas --
            </option>

            @foreach($kelas as $k)

                <option
                    value="{{ $k->id }}"
                    data-jurusan="{{ $k->jurusan_id }}"
                    data-wali="{{ $k->nama_wali ?? '-' }}">

                    {{ $k->nama_kelas }}

                </option>

            @endforeach

        </select>

        <!-- WALI -->
        <label>Wali Kelas</label>

        <input type="text"
               id="wali_kelas"
               class="readonly"
               placeholder="Otomatis dari kelas"
               readonly>

        <!-- NO ORTU -->
        <label>Nama Orang Tua</label>

        <input type="text"
               name="nama_ortu"
               placeholder="Masukkan nama orang tua">

        <label>No Orang Tua</label>

        <input type="text"
               name="no_ortu"
               placeholder="Contoh: 08123456789">

        <div class="btn-group">

            <a href="/dashboard/admin/siswa"
               class="btn btn-kembali">

                Kembali

            </a>

            <button type="submit"
                    class="btn btn-simpan">

                Simpan

            </button>

        </div>

    </form>

</div>
<script src="{{ asset('js/pages/dashboard-siswa-create.js') }}"></script>

</main>

</body>
</html>




