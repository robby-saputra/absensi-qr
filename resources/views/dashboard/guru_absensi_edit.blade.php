<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Absensi Siswa</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-guru.css') }}">
</head>

<body>
    @include('layouts.sidebar_guru')

    <main id="content" class="content">
        @include('layouts.alerts')

        <div class="attendance-panel">
            <div class="section-head">
                <div>
                    <h3>Edit Absensi Siswa</h3>
                    <p>Gunakan saat siswa lupa absen masuk atau pulang.</p>
                </div>
                <a href="/dashboard/guru/verifikasi-absensi?tanggal={{ $tanggal }}" class="btn disabled">Kembali</a>
            </div>

            @if ($errors->any())
                <div class="empty-state">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="/dashboard/guru/absensi/{{ $siswa->id }}/update"
                class="filter-box edit-absensi-form">
                @csrf

                <label>
                    Nama
                    <input type="text" value="{{ $siswa->nama }}" disabled>
                </label>

                <label>
                    Kelas
                    <input type="text" value="{{ $kelas->nama_kelas ?? '-' }}" disabled>
                </label>

                <label>
                    Tanggal
                    <input type="date" name="tanggal" value="{{ old('tanggal', $tanggal) }}" required>
                </label>

                <label>
                    Jam Masuk
                    <input type="time" name="jam_masuk"
                        value="{{ old('jam_masuk', $absensi ? substr((string) $absensi->jam_masuk, 0, 5) : '') }}">
                </label>

                <label>
                    Status Masuk
                    <select name="status_masuk">
                        <option value="">Belum Absen</option>
                        @foreach (['hadir', 'telat', 'izin', 'sakit'] as $status)
                            <option value="{{ $status }}"
                                {{ old('status_masuk', $absensi->status_masuk ?? '') == $status ? 'selected' : '' }}>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Jam Pulang
                    <input type="time" name="jam_pulang"
                        value="{{ old('jam_pulang', $absensi ? substr((string) $absensi->jam_pulang, 0, 5) : '') }}">
                </label>

                <label>
                    Status Pulang
                    <select name="status_pulang">
                        <option value="">Belum Pulang</option>
                        @foreach (['pulang', 'izin', 'sakit'] as $status)
                            <option value="{{ $status }}"
                                {{ old('status_pulang', $absensi->status_pulang ?? '') == $status ? 'selected' : '' }}>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <div class="filter-actions">
                    <button type="submit" class="btn">Simpan Perubahan</button>
                    <a href="/dashboard/guru/absensi/{{ $siswa->id }}/view?tanggal={{ $tanggal }}"
                        class="btn btn-success">View</a>
                </div>
            </form>
        </div>
    </main>
</body>

</html>
