<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Nilai</title>

    <style>
        body{
            font-family:Arial;
            background:#f5f6fa;
            padding:30px;
        }

        .box{
            width:400px;
            background:white;
            padding:25px;
            border-radius:8px;
        }

        input{
            width:100%;
            padding:10px;
            margin-top:6px;
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

    <h2>Edit Nilai</h2>

    <form method="POST"
          action="/dashboard/admin/nilai/update/{{ $nilai->id }}">

        @csrf

        <label>Nilai</label>
        <input type="number"
               name="nilai"
               value="{{ $nilai->nilai }}">

        <button type="submit">Update</button>

    </form>

</div>

</body>
</html>