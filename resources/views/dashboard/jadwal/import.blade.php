<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Import Jadwal</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-siswa-import.css') }}">
</head>
<body>
@include('layouts.sidebar_admin')

<main id="content" class="content">
    <div class="box">
        <h2>Import Jadwal Pelajaran</h2>

        <div class="info">
            Format kolom: <code>tahun_ajaran</code>, <code>semester</code>, <code>kelas</code>,
            <code>hari</code>, <code>jam_mulai</code>, <code>jam_selesai</code>,
            <code>mapel</code>, <code>guru</code>, <code>guru_pengganti</code>, <code>keterangan</code>.
            <br><br>
            Nama kelas, mapel, dan guru harus sesuai data master. Sistem akan menolak baris yang bentrok jadwal.
        </div>

        @if(session('import_result'))
            @php($result = session('import_result'))
            <div class="success">
                Berhasil import: <b>{{ $result['success'] }}</b> jadwal
                <br>
                Gagal: <b>{{ $result['failed'] }}</b> baris
            </div>

            @if(!empty($result['errors']))
                <div class="error">
                    <ul class="form-errors">
                        @foreach($result['errors'] as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif

        <form method="POST" action="/dashboard/admin/jadwal/import" enctype="multipart/form-data">
            @csrf
            <input type="file" name="file" accept=".xlsx,.csv,.txt" required>
            <button type="submit" class="btn import">Import Jadwal</button>
        </form>

        <a href="/dashboard/admin/jadwal/template" class="btn template">Download Template</a>
        <a href="/dashboard/admin/jadwal" class="btn back">Kembali</a>
    </div>
</main>
</body>
</html>
