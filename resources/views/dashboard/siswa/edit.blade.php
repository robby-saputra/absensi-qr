<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Siswa</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-siswa-edit.css') }}">
</head>

<body>

    @include('layouts.sidebar_admin')

    <main id="content" class="content">

        <div class="container">

            <h2>Edit Siswa</h2>

            @if ($errors->any())
                <div class="error">
                    <ul class="form-errors">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="/dashboard/admin/siswa/update/{{ $siswa->id }}">

                @csrf

                <label>Nama Siswa</label>
                <input type="text" name="nama" value="{{ $siswa->nama }}">

                <label>Username</label>
                <input type="text" name="username" value="{{ $siswa->username }}">

                <label>NIS</label>
                <input type="text" name="nis" value="{{ $siswa->nis }}">

                <label>Kelas</label>
                <select name="kelas_id">

                    <option value="">-- Pilih Kelas --</option>

                    @foreach ($kelas as $k)
                        <option value="{{ $k->id }}" {{ $siswa->kelas_id == $k->id ? 'selected' : '' }}>
                            {{ $k->nama_kelas }}
                            @if (!empty($k->nama_wali))
                                - Wali: {{ $k->nama_wali }}
                            @endif
                        </option>
                    @endforeach

                </select>

                <label>Nama Orang Tua</label>
                <input type="text" name="nama_ortu" value="{{ $siswa->nama_ortu }}">

                <label>No Orang Tua</label>
                <input type="text" name="no_ortu" value="{{ $siswa->no_ortu }}">

                <br>

                <a href="/dashboard/admin/siswa" class="btn back">
                    Kembali
                </a>

                <button type="submit" class="btn">
                    Update Siswa
                </button>

            </form>

        </div>

    </main>

</body>

</html>
