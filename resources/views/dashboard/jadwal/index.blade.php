<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')

    <meta charset="UTF-8">

    <title>

        Kelola Jadwal

    </title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-jadwal-index.css') }}">

</head>



<body>

    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        @include('layouts.alerts')



        <h2>

            Kelola Jadwal Pelajaran

        </h2>




        <p>

            <a class="btn" href="/dashboard/admin">

                Kembali

            </a>




            <a class="btn" href="/dashboard/admin/jadwal/create">

                Tambah Jadwal

            </a>

            <a class="btn" target="_blank" href="/dashboard/admin/pdf/jadwal">

                PDF Resmi

            </a>


        </p>








        <table>


            <tr>

                <th>
                    Kelas
                </th>


                <th>
                    Hari
                </th>


                <th>
                    Jam
                </th>


                <th>
                    Mapel
                </th>


                <th>
                    Guru Utama
                </th>



                <th>
                    Status
                </th>



                <th>
                    Keterangan
                </th>



                <th>
                    Aksi
                </th>


            </tr>





            @foreach ($jadwal as $j)
                <tr>



                    <td>

                        {{ $j->nama_kelas }}

                    </td>




                    <td>

                        {{ $j->hari }}

                    </td>





                    <td>

                        {{ $j->jam_mulai }}

                        -

                        {{ $j->jam_selesai }}

                    </td>






                    <td>

                        {{ $j->nama_mapel }}

                    </td>







                    <td>

                        {{ $j->nama_guru }}

                    </td>








                    <td>


                        <span class="badge normal">

                            Normal

                        </span>


                    </td>









                    <td>

                        {{ $j->keterangan ?? '-' }}

                    </td>








                    <td>

                        <a class="btn edit" href="/dashboard/admin/jadwal/edit/{{ $j->id }}">

                            Edit

                        </a>


                        <a class="btn hapus" href="/dashboard/admin/jadwal/delete/{{ $j->id }}"
                            data-confirm="Data akan dihapus dari daftar utama.">

                            Hapus

                        </a>


                    </td>




                </tr>
            @endforeach




        </table>




    </main>

</body>

</html>
