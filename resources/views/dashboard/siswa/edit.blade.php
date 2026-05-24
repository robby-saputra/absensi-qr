<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Siswa</title>

    <style>
        body{
            font-family:Arial;
            background:#f5f6fa;
            padding:30px;
        }

        .container{
            max-width:700px;
            margin:auto;
            background:white;
            padding:25px;
            border-radius:10px;
            box-shadow:0 2px 8px rgba(0,0,0,0.08);
        }

        h2{
            margin-top:0;
            margin-bottom:20px;
        }

        label{
            font-weight:bold;
        }

        input,
        select{
            width:100%;
            padding:10px;
            margin-top:5px;
            margin-bottom:15px;
            border:1px solid #ccc;
            border-radius:5px;
            box-sizing:border-box;
        }

        .btn{
            padding:10px 16px;
            background:#273c75;
            color:white;
            text-decoration:none;
            border:none;
            border-radius:5px;
            cursor:pointer;
        }

        .btn:hover{
            background:#192a56;
        }

        .back{
            background:#7f8fa6;
            margin-right:10px;
        }

        .back:hover{
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

<div class="container">

    <h2>Edit Siswa</h2>

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
          action="/dashboard/admin/siswa/update/{{ $siswa->id }}">

        @csrf

        <label>Nama Siswa</label>
        <input type="text"
               name="nama"
               value="{{ $siswa->nama }}">

        <label>Username</label>
        <input type="text"
               name="username"
               value="{{ $siswa->username }}">

        <label>NIS</label>
        <input type="text"
               name="nis"
               value="{{ $siswa->nis }}">

        <label>Kelas</label>
        <select name="kelas_id">

            <option value="">-- Pilih Kelas --</option>

            @foreach($kelas as $k)

                <option value="{{ $k->id }}"
                    {{ $siswa->kelas_id == $k->id ? 'selected' : '' }}>
                    {{ $k->nama_kelas }}
                    @if(!empty($k->nama_wali))
                        - Wali: {{ $k->nama_wali }}
                    @endif
                </option>

            @endforeach

        </select>

        <label>No Orang Tua</label>
        <input type="text"
               name="no_ortu"
               value="{{ $siswa->no_ortu }}">

        <br>

        <a href="/dashboard/admin/siswa" class="btn back">
            Kembali
        </a>

        <button type="submit" class="btn">
            Update Siswa
        </button>

    </form>

</div>

</body>
</html>
