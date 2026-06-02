<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>{{ $mode === 'edit' ? 'Edit' : 'Tambah' }} Absensi Harian</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        <div class="rekap-head">
            <div>
                <h1>{{ $mode === 'edit' ? 'Edit' : 'Tambah' }} Absensi Harian</h1>
                <p>Data ini adalah absensi harian sekolah yang sumber normalnya dari guru piket.</p>
            </div>
            <div>
                <a href="/dashboard/admin/absensi" class="btn back">Kembali</a>
            </div>
        </div>

        <form method="POST"
            action="{{ $mode === 'edit' ? '/dashboard/admin/absensi/update/' . $absensi->id : '/dashboard/admin/absensi/store' }}"
            class="rekap-filter">
            @csrf
            <select name="tahun_ajaran_id">
                <option value="">Tahun Ajaran Aktif</option>
                @foreach ($tahunAjaran as $ta)
                    <option value="{{ $ta->id }}"
                        {{ old('tahun_ajaran_id', $tahunAjaranId) == $ta->id ? 'selected' : '' }}>
                        {{ $ta->nama }} - {{ ucfirst($ta->semester) }} {{ $ta->aktif ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
            <select name="id_siswa" required>
                <option value="">Pilih Siswa</option>
                @foreach ($siswa as $s)
                    <option value="{{ $s->id }}"
                        {{ old('id_siswa', $absensi->id_siswa ?? '') == $s->id ? 'selected' : '' }}>
                        {{ $s->nama }} {{ $s->nis ? '- ' . $s->nis : '' }}
                    </option>
                @endforeach
            </select>
            <input type="date" name="tanggal"
                value="{{ old('tanggal', $absensi->tanggal ?? now()->toDateString()) }}" required>
            <input type="time" name="jam_masuk" value="{{ old('jam_masuk', $absensi->jam_masuk ?? '') }}">
            <select name="status_masuk">
                <option value="">Status Masuk</option>
                @foreach (['hadir', 'telat', 'izin', 'sakit', 'alfa'] as $status)
                    <option value="{{ $status }}"
                        {{ old('status_masuk', $absensi->status_masuk ?? '') === $status ? 'selected' : '' }}>
                        {{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <input type="time" name="jam_pulang" value="{{ old('jam_pulang', $absensi->jam_pulang ?? '') }}">
            <select name="status_pulang">
                <option value="">Status Pulang</option>
                @foreach (['pulang', 'pulang_cepat', 'izin', 'sakit', 'alfa'] as $status)
                    <option value="{{ $status }}"
                        {{ old('status_pulang', $absensi->status_pulang ?? '') === $status ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Simpan</button>
        </form>
    </main>
</body>

</html>
