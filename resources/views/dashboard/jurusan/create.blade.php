<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Jurusan</title>

    <style>
        body{
            font-family:Arial, sans-serif;
            background:#f5f6fa;
            padding:30px;
        }

        .box{
            width:500px;
            margin:auto;
            background:white;
            padding:25px;
            border-radius:10px;
            box-shadow:0 2px 8px rgba(0,0,0,0.08);
        }

        h2{
            margin-top:0;
            margin-bottom:20px;
            color:#273c75;
        }

        label{
            font-weight:bold;
            display:block;
            margin-bottom:5px;
        }

        input{
            width:100%;
            padding:10px;
            margin-bottom:15px;
            border:1px solid #ccc;
            border-radius:5px;
            box-sizing:border-box;
        }

        .btn-group{
            display:flex;
            gap:10px;
        }

        .btn{
            padding:10px 16px;
            border:none;
            border-radius:5px;
            cursor:pointer;
            text-decoration:none;
            color:white;
            font-size:14px;
        }

        .btn-simpan{
            background:#273c75;
        }

        .btn-simpan:hover{
            background:#192a56;
        }

        .btn-kembali{
            background:#7f8fa6;
        }

        .btn-kembali:hover{
            background:#718093;
        }

        .error{
            background:#e84118;
            color:white;
            padding:10px;
            border-radius:5px;
            margin-bottom:15px;
        }
    </style>
</head>
<body>

<div class="box">

    <h2>Tambah Jurusan</h2>

    @if ($errors->any())

        <div class="error">

            <ul style="margin:0; padding-left:20px;">

                @foreach ($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif

    <form method="POST"
          action="/dashboard/admin/jurusan/store">

        @csrf

        <label>Nama Jurusan</label>

        <input type="text"
               name="nama_jurusan"
               placeholder="Contoh: Teknik Komputer Jaringan">

        <label>Kode Jurusan</label>

        <input type="text"
               name="kode_jurusan"
               placeholder="Contoh: TKJ">

        <div class="btn-group">

            <a href="/dashboard/admin/jurusan"
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

</body>
</html>