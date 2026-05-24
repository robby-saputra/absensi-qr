<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Wali Kelas</title>

    <style>
        body{
            font-family:Arial, sans-serif;
            background:#f5f6fa;
            padding:30px;
        }

        .box{
            width:550px;
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

        input,
        select{
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

        .info{
            background:#dff9fb;
            padding:12px;
            border-radius:6px;
            margin-bottom:20px;
            color:#130f40;
        }
    </style>
</head>
<body>

<div class="box">

    <h2>Tambah Wali Kelas</h2>

    <div class="info">
        Pilih guru yang akan menjadi wali kelas.
        Setiap guru hanya dapat menjadi wali untuk 1 kelas.
    </div>

    @if(session('error'))

        <div class="error">
            {{ session('error') }}
        </div>

    @endif

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
          action="/dashboard/admin/wali-kelas/store">

        @csrf

        <label>Guru</label>

        <select name="guru_id" required>

            <option value="">
                -- Pilih Guru --
            </option>

            @foreach($guru as $g)

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

            @foreach($kelas as $k)

                <option value="{{ $k->id }}">
                    {{ $k->nama_kelas }}
                </option>

            @endforeach

        </select>

        <div class="btn-group">

            <a href="/dashboard/admin/wali-kelas"
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