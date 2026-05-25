<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Kelas</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-kelas-create.css') }}">
</head>
<body>

@include('layouts.sidebar_admin')

<main id="content" class="content">

<div class="box">

    <h2>Tambah Kelas</h2>

    @if(session('error'))

        <div class="error">
            {{ session('error') }}
        </div>

    @endif

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
          action="/dashboard/admin/kelas/store">

        @csrf

        <label>Nama Kelas</label>

        <input type="text"
               name="nama_kelas"
               placeholder="Contoh: X TKJ 1">

        <label>Jurusan</label>

        <select name="jurusan_id">

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

        <label>Wali Kelas</label>

        <select name="wali_kelas_id">

            <option value="">
                -- Pilih Wali Kelas --
            </option>

            @foreach($guru as $g)

                <option value="{{ $g->id }}">

                    {{ $g->nama }}

                </option>

            @endforeach

        </select>

        <div class="btn-group">

            <a href="/dashboard/admin/kelas"
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

</main>

</body>
</html>





