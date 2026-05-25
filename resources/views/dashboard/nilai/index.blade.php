<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Monitoring Nilai</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-nilai-index.css') }}">
</head>
<body>

@include('layouts.sidebar_admin')

<main id="content" class="content">

<h2>Monitoring Nilai Siswa</h2>

<p>
    <a href="/dashboard/admin">Kembali</a>
</p>

<table>

    <tr>
        <th>Siswa</th>
        <th>Kelas</th>
        <th>Mapel</th>
        <th>Guru</th>
        <th>Jenis</th>
        <th>Nilai</th>
        <th>Aksi</th>
    </tr>

    @foreach($nilai as $n)
        <tr>
            <td>{{ $n->nama_siswa }}</td>
            <td>{{ $n->nama_kelas ?? '-' }}</td>
            <td>{{ $n->nama_mapel }}</td>
            <td>{{ $n->nama_guru }}</td>
            <td>{{ $n->jenis_nilai }}</td>
            <td>{{ $n->nilai }}</td>

            <td>
                <a class="btn edit"
                   href="/dashboard/admin/nilai/edit/{{ $n->id }}">
                    Edit
                </a>

                <a class="btn hapus"
                   href="/dashboard/admin/nilai/delete/{{ $n->id }}">
                    Hapus
                </a>
            </td>
        </tr>
    @endforeach

</table>

</main>

</body>
</html>





