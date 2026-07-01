{{-- File ini menampilkan form tambah jurusan baru sebagai data master program keahlian di sekolah. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Jurusan</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-jurusan-create.css') }}">
</head>

<body>

    @include('layouts.sidebar_admin')

    <main id="content" class="content">

        <div class="box">

            <h2>Tambah Jurusan</h2>

            @if ($errors->any())

                <div class="error">

                    <ul class="form-errors">

                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach

                    </ul>

                </div>

            @endif

            <form method="POST" action="/dashboard/admin/jurusan/store">

                @csrf

                <label>Nama Jurusan</label>

                <input type="text" name="nama_jurusan" placeholder="Contoh: Teknik Komputer Jaringan">

                <label>Kode Jurusan</label>

                <input type="text" name="kode_jurusan" placeholder="Contoh: TKJ">

                <div class="btn-group">

                    <a href="/dashboard/admin/jurusan" class="btn btn-kembali">

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
