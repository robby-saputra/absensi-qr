<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Absensi Siswa</title>

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            font-family:Arial;
            background:#f5f6fa;
        }

        .sidebar{
            width:250px;
            height:100vh;
            background:#273c75;
            position:fixed;
            left:0;
            top:0;
            padding:20px;
            color:white;
        }

        .sidebar h2{
            margin-bottom:30px;
        }

        .sidebar a{
            display:block;
            padding:12px;
            margin-bottom:10px;
            background:rgba(255,255,255,0.1);
            color:white;
            text-decoration:none;
            border-radius:6px;
        }

        .content{
            margin-left:250px;
            padding:30px;
        }

        .topbar{
            background:white;
            padding:20px;
            border-radius:10px;
            margin-bottom:25px;
        }

        .table-box{
            background:white;
            padding:20px;
            border-radius:10px;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        th{
            background:#273c75;
            color:white;
            padding:12px;
        }

        td{
            padding:12px;
            border-bottom:1px solid #ddd;
        }

    </style>
</head>
<body>

<div class="sidebar">

    <h2>Wali Kelas</h2>

    <a href="/dashboard/wali">Dashboard</a>
    <a href="/dashboard/wali/siswa">Data Siswa</a>
    <a href="/dashboard/wali/nilai">Monitoring Nilai</a>
    <a href="/dashboard/wali/absensi">Absensi Siswa</a>
    <a href="/logout">Logout</a>

</div>

<div class="content">

    <div class="topbar">

        <h2>Absensi Siswa</h2>

        <br>

        <p>
            Kelas:
            <strong>{{ $wali->nama_kelas }}</strong>
        </p>

    </div>

    <div class="table-box">

        <table>

            <tr>
                <th>Nama</th>
                <th>Tanggal</th>
                <th>Jam Masuk</th>
                <th>Status</th>
            </tr>

            @foreach($absensi as $a)

                <tr>
                    <td>{{ $a->nama }}</td>
                    <td>{{ $a->tanggal }}</td>
                    <td>{{ $a->jam_masuk }}</td>
                    <td>{{ $a->status_masuk }}</td>
                </tr>

            @endforeach

        </table>

    </div>

</div>

</body>
</html>