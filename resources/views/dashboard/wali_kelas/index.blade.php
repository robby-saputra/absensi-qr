<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Kelola Wali Kelas
    </title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-wali_kelas-index.css') }}">

</head>

<body>

    @include('layouts.sidebar_admin')

    <main id="content" class="content">



        <div class="container">


            <div class="top">


                <h2>
                    Kelola Wali Kelas
                </h2>



                <div class="top-actions">

                    <a href="/dashboard/admin" class="btn btn-kembali">

                        Kembali

                    </a>


                    <a href="/dashboard/admin/wali-kelas/create" class="btn btn-tambah">

                        Tambah Wali Kelas

                    </a>

                    <a href="/dashboard/admin/rekap/wali-kelas-pdf" target="_blank" class="btn">

                        PDF Resmi

                    </a>

                </div>


            </div>



            {{-- SUCCESS --}}
            @if (session('success'))
                <div class="alert success">

                    {{ session('success') }}

                </div>
            @endif



            {{-- ERROR --}}
            @if (session('error'))
                <div class="alert error">

                    {{ session('error') }}

                </div>
            @endif




            <div class="table-wrapper">


                <table>


                    <tr>

                        <th width="60">
                            No
                        </th>

                        <th>
                            Kelas
                        </th>

                        <th>
                            Wali Kelas
                        </th>

                        <th>
                            Jumlah Siswa
                        </th>

                        <th>
                            Username
                        </th>

                        <th width="180">
                            Aksi
                        </th>

                    </tr>



                    @forelse($wali as $w)
                        <tr>


                            <td>

                                {{ $loop->iteration }}

                            </td>



                            <td>

                                <span class="badge-kelas">

                                    {{ $w->nama_kelas }}

                                </span>

                            </td>



                            <td>

                                {{ $w->nama ?? '-' }}

                            </td>



                            <td>

                                <span class="badge-siswa">

                                    {{ $w->jumlah_siswa }}

                                    Siswa

                                </span>

                            </td>



                            <td>

                                {{ $w->username ?? '-' }}

                            </td>




                            <td class="aksi">


                                <!-- EDIT -->
                                <a href="/dashboard/admin/wali-kelas/edit/{{ $w->id }}" class="btn-edit">

                                    Edit

                                </a>



                                <!-- HAPUS -->
                                <a href="/dashboard/admin/wali-kelas/delete/{{ $w->id }}" class="btn-hapus"
                                    data-confirm="Data akan dihapus dari daftar utama.">

                                    Hapus

                                </a>


                            </td>


                        </tr>

                    @empty

                        <tr>

                            <td colspan="6" class="kosong">

                                Data wali kelas belum tersedia

                            </td>

                        </tr>
                    @endforelse


                </table>


            </div>



        </div>




    </main>

</body>

</html>
