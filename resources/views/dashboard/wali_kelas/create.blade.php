<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Wali Kelas</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-wali_kelas-create.css') }}">
</head>

<body>

    @include('layouts.sidebar_admin')

    <main id="content" class="content">

        <div class="box">

            <h2>Tambah Wali Kelas</h2>

            <div class="info">
                Pilih guru yang akan menjadi wali kelas.
                Setiap guru hanya dapat menjadi wali untuk 1 kelas.
            </div>

            @if (session('error'))
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

            <form method="POST" action="/dashboard/admin/wali-kelas/store">

                @csrf

                <label>Guru</label>

                <select name="guru_id" required>

                    <option value="">
                        -- Pilih Guru --
                    </option>

                    @foreach ($guru as $g)
                        <option value="{{ $g->id }}">
                            {{ $g->nama }}
                        </option>
                    @endforeach

                </select>

                <label>Kelas</label>

                <select name="kelas_id" required>

                    <option value="">
                        -- Pilih Kelas --
                    </option>

                    @foreach ($kelas as $k)
                        <option value="{{ $k->id }}">
                            {{ $k->nama_kelas }}
                        </option>
                    @endforeach

                </select>

                <div class="btn-group">

                    <a href="/dashboard/admin/wali-kelas" class="btn btn-kembali">

                        Kembali

                    </a>

                    <button type="submit" class="btn btn-simpan">

                        Simpan

                    </button>

                </div>

            </form>

        </div>

    </main>

</body>

</html>
