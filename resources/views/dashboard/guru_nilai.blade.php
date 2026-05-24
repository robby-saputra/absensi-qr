<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Input Nilai Guru</title>

    <style>
        body{
            font-family:Arial;
            background:#f5f6fa;
            padding:30px;
        }

        table{
            width:100%;
            border-collapse:collapse;
            background:white;
        }

        th, td{
            border:1px solid #ddd;
            padding:10px;
            text-align:left;
        }

        th{
            background:#273c75;
            color:white;
        }

        input, select{
            width:100%;
            padding:8px;
        }

        button{
            padding:8px 14px;
            background:#273c75;
            color:white;
            border:none;
            cursor:pointer;
        }

        .top{
            margin-bottom:20px;
        }

        .success{
            background:#dff9fb;
            padding:10px;
            margin-bottom:15px;
            color:#130f40;
        }

        .btn-back{
            display:inline-block;
            margin-bottom:15px;
            padding:8px 14px;
            background:#273c75;
            color:white;
            text-decoration:none;
            border-radius:5px;
        }
    </style>
</head>
<body>

<a class="btn-back" href="/dashboard/guru">
    Kembali
</a>

<div class="top">
    <h2>Input Nilai Siswa</h2>

    <p>
        <strong>Kelas:</strong>
        {{ $jadwal->nama_kelas }}
    </p>

    <p>
        <strong>Mapel:</strong>
        {{ $jadwal->nama_mapel }}
    </p>
</div>

@if(session('success'))
    <div class="success">
        {{ session('success') }}
    </div>
@endif

<table>

    <tr>
        <th>Nama Siswa</th>
        <th>Jenis Nilai</th>
       <th>Nilai</th>
<th>Keterangan</th>
<th>Aksi</th>
    </tr>

    @foreach($siswa as $s)

        <tr>

            <form method="POST"
                  action="/dashboard/guru/nilai/store">

                @csrf

                <td>
                    {{ $s->nama }}

                    <input type="hidden"
                           name="siswa_id"
                           value="{{ $s->id }}">

                    <input type="hidden"
                           name="mapel_id"
                           value="{{ $jadwal->mapel_id }}">

                    <input type="hidden"
                           name="guru_id"
                           value="{{ $user->id }}">
                </td>

                <td>
                    <select name="jenis_nilai">
                        <option value="Tugas">Tugas</option>
                        <option value="UH">UH</option>
                        <option value="UTS">UTS</option>
                        <option value="UAS">UAS</option>
                    </select>
                </td>

             <td>
    <input type="number"
           name="nilai"
           min="0"
           max="100">
</td>

<td>
    <input type="text"
           name="keterangan"
           placeholder="Contoh: Tidak mengumpulkan tugas">
</td>
                <td>
                    <button type="submit">
                        Simpan
                    </button>
                </td>

            </form>

        </tr>

    @endforeach

</table>

</body>
</html>