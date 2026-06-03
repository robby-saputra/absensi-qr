<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Kelola Jurusan</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-jurusan-index.css') }}">
</head>

<body>

    @include('layouts.sidebar_admin')

    <main id="content" class="content">

        <div class="container">
            @include('layouts.alerts')

            <div class="top">

                <h2>Kelola Jurusan</h2>

                <div>

                    <a href="/dashboard/admin" class="btn">

                        Kembali

                    </a>

                    <a href="/dashboard/admin/jurusan/create" class="btn">

                        Tambah Jurusan

                    </a>

                    <a href="/dashboard/admin/pdf/jurusan" target="_blank" class="btn">
                        PDF Resmi
                    </a>

                </div>

            </div>

            <table>

                <tr>
                    <th width="60">No</th>
                    <th>Nama Jurusan</th>
                    <th>Kode</th>
                    <th width="120">Aksi</th>
                </tr>

                @foreach ($jurusan as $j)
                    <tr>

                        <td>
                            {{ $loop->iteration }}
                        </td>

                        <td>
                            {{ $j->nama_jurusan }}
                        </td>

                        <td>
                            {{ $j->kode_jurusan }}
                        </td>

                        <td>
                            <div class="aksi">

                                <a href="/dashboard/admin/jurusan/edit/{{ $j->id }}" class="btn edit">

                                    Edit

                                </a>

                                <a href="/dashboard/admin/jurusan/delete/{{ $j->id }}" class="btn hapus"
                                    data-confirm="Data akan dipindahkan ke arsip dan masih bisa dipulihkan dari menu Arsip Data.">

                                    Hapus

                                </a>

                            </div>

                        </td>

                    </tr>
                @endforeach

            </table>

        </div>


    </main>

</body>

</html>
