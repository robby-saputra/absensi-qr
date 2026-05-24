<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah User</title>

    <style>
        body{
            font-family:Arial;
            background:#f5f6fa;
            padding:30px;
        }

        .box{
            width:500px;
            background:white;
            padding:25px;
            border-radius:8px;
        }

        input, select{
            width:100%;
            padding:10px;
            margin-top:5px;
            margin-bottom:15px;
        }

        button{
            padding:10px 16px;
            background:#273c75;
            color:white;
            border:none;
        }
    </style>
</head>
<body>

<div class="box">

    <h2>Tambah User</h2>

    <form method="POST"
          action="/dashboard/admin/users/store">

        @csrf

        <label>Nama</label>
        <input type="text" name="nama">

        <label>NUPTK</label>
        <input type="text" name="nuptk">

        <label>Username</label>
        <input type="text" name="username">

        <label>Password</label>
        <input type="text" name="password">

        <label>Role</label>
        <select name="role">
            <option value="admin">Admin</option>
            <option value="guru">Guru</option>
            <option value="piket">Piket</option>
            <option value="siswa">Siswa</option>
        </select>

        <label>Kelas</label>
        <input type="text" name="kelas">

        <label>No Orang Tua</label>
        <input type="text" name="no_ortu">

        <button type="submit">
            Simpan
        </button>

    </form>

</div>

</body>
</html>
