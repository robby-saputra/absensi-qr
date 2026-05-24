<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Kelola Wali Kelas
    </title>


    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{

            font-family:Arial,sans-serif;

            background:#f1f5f9;

            padding:30px;

        }


        .container{

            background:white;

            border-radius:14px;

            padding:25px;

            box-shadow:
            0 4px 15px rgba(0,0,0,0.08);

        }


        .top{

            display:flex;

            justify-content:space-between;

            align-items:center;

            flex-wrap:wrap;

            gap:15px;

            margin-bottom:25px;

        }


        h2{

            color:#1e293b;

            font-size:28px;

        }



        /* BUTTON */

        .btn{

            display:inline-block;

            padding:10px 16px;

            border-radius:8px;

            text-decoration:none;

            color:white;

            font-size:14px;

            transition:.2s;

        }


        .btn-kembali{

            background:#64748b;

        }

        .btn-kembali:hover{

            background:#475569;

        }



        .btn-tambah{

            background:#2563eb;

        }

        .btn-tambah:hover{

            background:#1d4ed8;

        }



        /* ALERT */

        .alert{

            padding:14px;

            border-radius:10px;

            margin-bottom:20px;

            font-size:14px;

        }

        .success{

            background:#dcfce7;

            color:#166534;

        }

        .error{

            background:#fee2e2;

            color:#991b1b;

        }



        /* TABLE */

        .table-wrapper{

            overflow-x:auto;

        }


        table{

            width:100%;

            border-collapse:collapse;

            min-width:900px;

        }


        th{

            background:#1e293b;

            color:white;

            padding:14px;

            text-align:left;

            font-size:14px;

        }


        td{

            padding:14px;

            border-bottom:1px solid #e2e8f0;

            vertical-align:middle;

            font-size:14px;

        }


        tr:hover{

            background:#f8fafc;

        }



        /* BADGE KELAS */

        .badge-kelas{

            background:#dbeafe;

            color:#1d4ed8;

            padding:7px 12px;

            border-radius:999px;

            font-size:13px;

            font-weight:bold;

            display:inline-block;

        }



        /* BADGE SISWA */

        .badge-siswa{

            background:#dcfce7;

            color:#166534;

            padding:7px 12px;

            border-radius:999px;

            font-size:13px;

            font-weight:bold;

            display:inline-block;

        }



        /* AKSI */

        .aksi{

            display:flex;

            gap:8px;

            justify-content:center;

            flex-wrap:wrap;

        }


        .btn-edit{

            background:#0ea5e9;

            color:white;

            padding:8px 14px;

            border-radius:7px;

            text-decoration:none;

            font-size:13px;

        }

        .btn-edit:hover{

            background:#0284c7;

        }


        .btn-hapus{

            background:#ef4444;

            color:white;

            padding:8px 14px;

            border-radius:7px;

            text-decoration:none;

            font-size:13px;

        }

        .btn-hapus:hover{

            background:#dc2626;

        }



        /* KOSONG */

        .kosong{

            text-align:center;

            padding:30px;

            color:#64748b;

        }



        /* RESPONSIVE */

        @media(max-width:768px){

            body{

                padding:15px;

            }

            h2{

                font-size:22px;

            }

        }

    </style>

</head>

<body>



<div class="container">


    <div class="top">


        <h2>
            Kelola Wali Kelas
        </h2>



        <div style="display:flex; gap:10px; flex-wrap:wrap;">

            <a
                href="/dashboard/admin"
                class="btn btn-kembali">

                Kembali

            </a>


            <a
                href="/dashboard/admin/wali-kelas/create"
                class="btn btn-tambah">

                Tambah Wali Kelas

            </a>

        </div>


    </div>



    {{-- SUCCESS --}}
    @if(session('success'))

        <div class="alert success">

            {{ session('success') }}

        </div>

    @endif



    {{-- ERROR --}}
    @if(session('error'))

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
                    <a

                        href="/dashboard/admin/wali-kelas/edit/{{ $w->id }}"

                        class="btn-edit">

                        Edit

                    </a>



                    <!-- HAPUS -->
                    <a

                        href="/dashboard/admin/wali-kelas/delete/{{ $w->id }}"

                        class="btn-hapus"

                        onclick="return confirm(
                            'Yakin hapus wali kelas?'
                        )">

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



</body>

</html>