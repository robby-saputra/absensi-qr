<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit Jadwal</title>
<link rel="stylesheet" href="{{ asset('css/pages/dashboard-jadwal-create.css') }}">
</head>

<body>
@include('layouts.sidebar_admin')

<main id="content" class="content">

<div class="box">
    <h2>Edit Jadwal Pelajaran</h2>

    @if(session('error'))
        <div class="info error">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="info error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="/dashboard/admin/jadwal/update/{{ $jadwal->id }}">
        @csrf

        <label>Kelas</label>
        <select name="kelas_id" required>
            @foreach($kelas as $k)
                <option value="{{ $k->id }}" {{ old('kelas_id', $jadwal->kelas_id) == $k->id ? 'selected' : '' }}>
                    {{ $k->nama_kelas }}
                </option>
            @endforeach
        </select>

        <label>Hari</label>
        <select name="hari" required>
            @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $h)
                <option value="{{ $h }}" {{ old('hari', $jadwal->hari) == $h ? 'selected' : '' }}>
                    {{ $h }}
                </option>
            @endforeach
        </select>

        <label>Jam Mulai</label>
        <input type="time" name="jam_mulai" value="{{ old('jam_mulai', substr($jadwal->jam_mulai, 0, 5)) }}" required>

        <label>Jam Selesai</label>
        <input type="time" name="jam_selesai" value="{{ old('jam_selesai', substr($jadwal->jam_selesai, 0, 5)) }}" required>

        <label>Mata Pelajaran</label>
        <select name="mapel_id" required>
            @foreach($mapels as $m)
                <option value="{{ $m->id }}" {{ old('mapel_id', $jadwal->mapel_id) == $m->id ? 'selected' : '' }}>
                    {{ $m->nama_mapel }}
                </option>
            @endforeach
        </select>

        <label>Guru Utama</label>
        <select name="guru_id" required>
            @foreach($guru as $g)
                <option value="{{ $g->id }}" {{ old('guru_id', $jadwal->guru_id) == $g->id ? 'selected' : '' }}>
                    {{ $g->nama }}
                </option>
            @endforeach
        </select>

        <label>Guru Pengganti</label>
        <select name="guru_pengganti_id">
            <option value="">Tidak Ada</option>
            @foreach($guru as $g)
                <option value="{{ $g->id }}" {{ old('guru_pengganti_id', $jadwal->guru_pengganti_id) == $g->id ? 'selected' : '' }}>
                    {{ $g->nama }}
                </option>
            @endforeach
        </select>

        <label>Keterangan</label>
        <textarea name="keterangan">{{ old('keterangan', $jadwal->keterangan) }}</textarea>

        <button type="submit">Update Jadwal</button>
        <a href="/dashboard/admin/jadwal" class="back">Kembali</a>
    </form>
</div>

</main>
</body>
</html>
