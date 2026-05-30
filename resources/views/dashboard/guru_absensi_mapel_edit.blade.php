<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit Absen Mapel</title>
<link rel="stylesheet" href="{{ asset('css/pages/dashboard-guru.css') }}">
</head>
<body>
@include('layouts.sidebar_guru')
<main id="content" class="content">
    <div class="attendance-panel">
        <div class="section-head">
            <div>
                <h3>Edit Absen Mapel</h3>
                <p>Guru mapel hanya memiliki satu absen mapel, tanpa pulang/akhir.</p>
            </div>
            <a href="/dashboard/guru/verifikasi-absensi?tanggal={{ $tanggal }}" class="btn disabled">Kembali</a>
        </div>

        @if($errors->any())
            <div class="empty-state">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="/dashboard/guru/absensi-mapel/{{ $jadwal->id }}/{{ $siswa->id }}/update" class="filter-box edit-absensi-form">
            @csrf
            <label>Nama<input type="text" value="{{ $siswa->nama }}" disabled></label>
            <label>Kelas<input type="text" value="{{ $jadwal->nama_kelas }}" disabled></label>
            <label>Mapel<input type="text" value="{{ $jadwal->nama_mapel }}" disabled></label>
            <label>Tanggal<input type="date" name="tanggal" value="{{ old('tanggal', $tanggal) }}" required></label>
            <label>Jam Absen Mapel<input type="time" name="jam_scan" value="{{ old('jam_scan', $absensiMapel ? substr((string) $absensiMapel->jam_scan, 0, 5) : '') }}"></label>
            <label>
                Status Absen Mapel
                <select name="status" required>
                    @foreach(['hadir','izin','sakit','alpa'] as $status)
                        <option value="{{ $status }}" {{ old('status', $absensiMapel->status ?? 'hadir') == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </label>
            <label>Catatan Guru
                <textarea name="catatan_guru" rows="3" placeholder="Contoh: siswa terlambat masuk kelas, izin UKS, atau keterangan lain">{{ old('catatan_guru', $absensiMapel->catatan_guru ?? '') }}</textarea>
            </label>
            <div class="filter-actions">
                <button type="submit" class="btn">Simpan Absen Mapel</button>
                <a href="/dashboard/guru/absensi-mapel/{{ $jadwal->id }}/{{ $siswa->id }}/view?tanggal={{ $tanggal }}" class="btn btn-success">View</a>
            </div>
        </form>
    </div>
</main>
</body>
</html>
