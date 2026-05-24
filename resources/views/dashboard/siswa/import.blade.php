<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Import Siswa</title>
    <style>
        body{font-family:Arial;background:#f5f6fa;padding:30px;}
        .box{max-width:760px;margin:auto;background:white;padding:25px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.08);}
        input{width:100%;padding:10px;margin:6px 0 15px;box-sizing:border-box;}
        .btn{padding:10px 16px;background:#273c75;color:white;border:none;border-radius:5px;text-decoration:none;cursor:pointer;}
        .back{background:#7f8fa6;}
        .success{background:#dff9fb;padding:12px;border-radius:6px;margin-bottom:15px;}
        .error{background:#ffe5e5;padding:12px;border-radius:6px;margin-top:10px;}
        code{background:#ecf0f1;padding:2px 5px;border-radius:4px;}
        table{width:100%;border-collapse:collapse;margin-top:15px;}
        th,td{border:1px solid #ddd;padding:8px;text-align:left;}
        th{background:#273c75;color:white;}
    </style>
</head>
<body>
<div class="box">
    <h2>Import Siswa dari Excel</h2>

    <p>Format kolom yang dibaca: <code>nama</code>, <code>nis</code>, <code>username</code>, <code>password</code>, <code>kelas</code>, <code>no_ortu</code>.</p>
    <p>Kolom <code>kelas</code> boleh berisi nama kelas, misalnya <code>X AK 1</code>, atau gunakan <code>kelas_id</code>.</p>

    @if(session('import_result'))
        @php($result = session('import_result'))
        <div class="success">
            Berhasil import: {{ $result['success'] }} siswa. Gagal: {{ $result['failed'] }} baris.
        </div>

        @if(!empty($result['errors']))
            <div class="error">
                @foreach($result['errors'] as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
    @endif

    @if ($errors->any())
        <div class="error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="/dashboard/admin/siswa/import" enctype="multipart/form-data">
        @csrf

        <label>File Excel / CSV</label>
        <input type="file" name="file" accept=".xlsx,.csv,.txt">

        <a class="btn back" href="/dashboard/admin/siswa">Kembali</a>
        <button class="btn" type="submit">Import</button>
    </form>

    <table>
        <tr>
            <th>Contoh nama</th>
            <th>nis</th>
            <th>username</th>
            <th>password</th>
            <th>kelas</th>
            <th>no_ortu</th>
        </tr>
        <tr>
            <td>Siswa Contoh</td>
            <td>1001</td>
            <td>siswa1001</td>
            <td>123456</td>
            <td>X AK 1</td>
            <td>08123456789</td>
        </tr>
    </table>
</div>
</body>
</html>
