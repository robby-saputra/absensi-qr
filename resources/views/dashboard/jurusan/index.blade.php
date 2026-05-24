<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Jurusan</title>

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

        .top{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:20px;
        }

        .btn{
            padding:8px 14px;
            background:#273c75;
            color:white;
            text-decoration:none;
            border-radius:5px;
        }

        .btn:hover{
            background:#192a56;
        }

        .hapus{
            background:#e84118;
        }

        .edit{
            background:#00a8ff;
        }

        .hapus:hover{
            background:#c23616;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        th, td{
            border:1px solid #ddd;
            padding:12px;
        }

        th{
            background:#273c75;
            color:white;
        }

        .success{
            color:green;
            margin-bottom:15px;
        }
    </style>
</head>
<body>

<div class="container">

    <div class="top">

        <h2>Kelola Jurusan</h2>

        <div>

            <a href="/dashboard/admin"
               class="btn">

                Kembali

            </a>

            <a href="/dashboard/admin/jurusan/create"
               class="btn">

                Tambah Jurusan

            </a>

        </div>

    </div>

    @if(session('success'))

        <div class="success">
            {{ session('success') }}
        </div>

    @endif

    <table>

        <tr>
            <th width="60">No</th>
            <th>Nama Jurusan</th>
            <th>Kode</th>
            <th width="120">Aksi</th>
        </tr>

        @foreach($jurusan as $j)

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

                    <a href="/dashboard/admin/jurusan/edit/{{ $j->id }}"
                       class="btn edit">

                        Edit

                    </a>

                    <a href="/dashboard/admin/jurusan/delete/{{ $j->id }}"
                       class="btn hapus"
                       onclick="return confirm('Hapus jurusan?')">

                        Hapus

                    </a>

                </td>

            </tr>

        @endforeach

    </table>

</div>

</body>
</html>
