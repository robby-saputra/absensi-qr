<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Kelas</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-kelas-edit.css') }}">
</head>
<body>

@include('layouts.sidebar_admin')

<main id="content" class="content">
<div class="box">
    <h2>Edit Kelas</h2>

    @if(session('error'))
        <div class="error">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="/dashboard/admin/kelas/update/{{ $kelas->id }}">
        @csrf

        <label>Nama Kelas</label>
        <input type="text" name="nama_kelas" value="{{ old('nama_kelas', $kelas->nama_kelas) }}">

        <label>Jurusan</label>
        <select name="jurusan_id">
            @foreach($jurusan as $j)
                <option value="{{ $j->id }}" {{ old('jurusan_id', $kelas->jurusan_id) == $j->id ? 'selected' : '' }}>
                    {{ $j->kode_jurusan }} - {{ $j->nama_jurusan }}
                </option>
            @endforeach
        </select>

        <label>Wali Kelas</label>
        <select name="wali_kelas_id">
            <option value="">-- Belum ada wali kelas --</option>
            @foreach($guru as $g)
                <option value="{{ $g->id }}" {{ old('wali_kelas_id', $kelas->wali_kelas_id) == $g->id ? 'selected' : '' }}>
                    {{ $g->nama }}
                </option>
            @endforeach
        </select>

        <a class="btn back" href="/dashboard/admin/kelas">Kembali</a>
        <button class="btn" type="submit">Simpan</button>
    </form>
</div>
</main>

</body>
</html>





