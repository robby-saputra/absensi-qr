<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Siswa</title>

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

        .sidebar a:hover{
            background:rgba(255,255,255,0.2);
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
            box-shadow:0 2px 10px rgba(0,0,0,0.05);
        }

        .table-box{
            background:white;
            padding:20px;
            border-radius:10px;
            box-shadow:0 2px 10px rgba(0,0,0,0.05);
        }

        table{
            width:100%;
            border-collapse:collapse;
            margin-top:15px;
        }

        table th{
            background:#273c75;
            color:white;
            padding:12px;
            text-align:left;
        }

        table td{
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

        <h2>Data Siswa</h2>

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
                <th>Username</th>
                <th>Kelas</th>
                <th>No Orang Tua</th>
            </tr>

            @foreach($siswa as $s)

                <tr>
                    <td>{{ $s->nama }}</td>
                    <td>{{ $s->username }}</td>
                    <td>{{ $s->nama_kelas ?? '-' }}</td>
                    <td>{{ $s->no_ortu }}</td>
                </tr>

            @endforeach

        </table>

    </div>

</div>

</body>
</html>
