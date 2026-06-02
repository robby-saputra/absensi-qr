<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Edit Absensi Harian</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-piket.css') }}">
</head>

<body>
    @include('layouts.sidebar_piket')
    <main id="content" class="content">
        <section class="card">
            <h3>Edit Absensi Harian</h3>
            <p class="muted">Gunakan untuk memperbaiki absen masuk/pulang harian siswa.</p>
            @if ($errors->any())
                <div class="alert error">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="/dashboard/piket/absensi/{{ $siswa->id }}/update" class="filter-box">
                @csrf
                <label>Nama <input type="text" value="{{ $siswa->nama }}" disabled></label>
                <label>Kelas <input type="text" value="{{ $kelas->nama_kelas ?? '-' }}" disabled></label>
                <label>Tanggal <input type="date" name="tanggal" value="{{ old('tanggal', $tanggal) }}"
                        required></label>
                <label>Jam Masuk <input type="time" name="jam_masuk"
                        value="{{ old('jam_masuk', $absensi ? substr((string) $absensi->jam_masuk, 0, 5) : '') }}"></label>
                <label>Status Masuk
                    <select name="status_masuk">
                        <option value="">Belum Absen</option>
                        @foreach (['hadir', 'telat', 'izin', 'sakit'] as $status)
                            <option value="{{ $status }}"
                                {{ old('status_masuk', $absensi->status_masuk ?? '') == $status ? 'selected' : '' }}>
                                {{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Jam Pulang <input type="time" name="jam_pulang"
                        value="{{ old('jam_pulang', $absensi ? substr((string) $absensi->jam_pulang, 0, 5) : '') }}"></label>
                <label>Status Pulang
                    <select name="status_pulang">
                        <option value="">Belum Pulang</option>
                        @foreach (['pulang', 'pulang_cepat', 'izin', 'sakit'] as $status)
                            <option value="{{ $status }}"
                                {{ old('status_pulang', $absensi->status_pulang ?? '') == $status ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Alasan Perubahan
                    <textarea name="catatan_piket" rows="3" required placeholder="Wajib diisi agar perubahan absensi punya bukti">{{ old('catatan_piket', $absensi->catatan_piket ?? '') }}</textarea>
                </label>
                <button class="btn" type="submit">Simpan</button>
                <a class="btn muted-btn"
                    href="/dashboard/piket/absensi/{{ $siswa->id }}/view?tanggal={{ $tanggal }}">View</a>
            </form>
        </section>
    </main>
</body>

</html>
