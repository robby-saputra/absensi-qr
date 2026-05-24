<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Wali Kelas</title>

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            font-family:Arial, sans-serif;
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

        .cards{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
            gap:20px;
            margin-bottom:30px;
        }

        .card{
            background:white;
            padding:25px;
            border-radius:10px;
            box-shadow:0 2px 10px rgba(0,0,0,0.05);
        }

        .card h3{
            font-size:18px;
            color:#555;
            margin-bottom:10px;
        }

        .card p{
            font-size:28px;
            font-weight:bold;
            color:#273c75;
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

        .badge{
            background:#44bd32;
            color:white;
            padding:5px 10px;
            border-radius:20px;
            font-size:12px;
        }

    </style>
</head>
<body>

<div class="sidebar">

    <h2>Wali Kelas</h2>

    <a href="/dashboard/wali">
        Dashboard
    </a>

    <a href="/dashboard/wali/siswa">
    Data Siswa
</a>

<a href="/dashboard/wali/nilai">
    Monitoring Nilai
</a>

<a href="/dashboard/wali/absensi">
    Absensi Siswa
</a>

    <a href="/logout">
        Logout
    </a>

</div>

<div class="content">

    <div class="topbar">

        <h2>Dashboard Wali Kelas</h2>

        <br>

        <p>
            Selamat datang,
            <strong>{{ $user->nama }}</strong>
        </p>

        <p>
            Kelas Wali:
            <strong>{{ $wali->nama_kelas }}</strong>
        </p>

    </div>

    <div class="cards">

        <div class="card">
            <h3>Total Siswa</h3>
            <p>{{ count($siswa) }}</p>
        </div>

        <div class="card">
            <h3>Kelas</h3>
            <p>{{ $wali->nama_kelas }}</p>
        </div>

    </div>

    <div class="table-box">

        <h3>Data Siswa</h3>

        <table>

            <tr>
                <th>Nama</th>
                <th>Username</th>
                <th>Kelas</th>
                <th>No Orang Tua</th>
                <th>Status</th>
            </tr>

            @foreach($siswa as $s)

                <tr>

                    <td>{{ $s->nama }}</td>

                    <td>{{ $s->username }}</td>

                    <td>{{ $s->nama_kelas ?? '-' }}</td>

                    <td>{{ $s->no_ortu }}</td>

                    <td>
                       @if($s->status_hari_ini == 'hadir')

    <span class="badge"
          style="background:#44bd32;">
        Hadir
    </span>

@elseif($s->status_hari_ini == 'telat')

    <span class="badge"
          style="background:#e67e22;">
        Telat
    </span>

@else

    <span class="badge"
          style="background:red;">
        Belum Absen
    </span>

@endif
                    </td>

                </tr>

            @endforeach

        </table>

    </div>

</div>

</body>
</html>
