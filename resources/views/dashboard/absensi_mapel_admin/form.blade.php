{{-- File ini menampilkan form absensi mata pelajaran untuk admin saat membuat atau memperbarui data kehadiran per jam pelajaran. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mode === 'edit' ? 'Edit' : 'Tambah' }} Absensi Mapel</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        <div class="rekap-head">
            <div>
                <h1>{{ $mode === 'edit' ? 'Edit' : 'Tambah' }} Absensi Mapel</h1>
                <p>Data ini adalah absensi per mapel, bukan absensi harian guru piket.</p>
            </div>
            <div>
                <a href="/dashboard/admin/absensi-mapel" class="btn back">Kembali</a>
            </div>
        </div>

        <form method="POST"
            action="{{ $mode === 'edit' ? '/dashboard/admin/absensi-mapel/update/' . $absensi->id : '/dashboard/admin/absensi-mapel/store' }}"
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
            <select name="jadwal_id" required>
                <option value="">Pilih Jadwal Mapel</option>
                @foreach ($jadwal as $j)
                    <option value="{{ $j->id }}"
                        {{ old('jadwal_id', $absensi->jadwal_id ?? '') == $j->id ? 'selected' : '' }}>
                        {{ $j->nama_kelas }} - {{ $j->nama_mapel }} - {{ ucfirst($j->hari) }}
                        {{ labelJadwalJp($j, false) }} · {{ substr($j->jam_mulai,0,5) }}-{{ substr($j->jam_selesai,0,5) }} - {{ $j->nama_guru }}
                    </option>
                @endforeach
            </select>
            <select name="siswa_id" required>
                <option value="">Pilih Siswa</option>
                @foreach ($siswa as $s)
                    <option value="{{ $s->id }}"
                        {{ old('siswa_id', $absensi->siswa_id ?? '') == $s->id ? 'selected' : '' }}>
                        {{ $s->nama }} {{ $s->nis ? '- ' . $s->nis : '' }}
                    </option>
                @endforeach
            </select>
            <input type="date" name="tanggal"
                value="{{ old('tanggal', $absensi->tanggal ?? now()->toDateString()) }}" required>
            <input type="time" name="jam_scan" value="{{ old('jam_scan', $absensi->jam_scan ?? '') }}">
            <select name="status" required>
                @foreach (['hadir', 'telat', 'izin', 'sakit', 'alfa'] as $status)
                    <option value="{{ $status }}"
                        {{ old('status', $absensi->status ?? 'hadir') === $status ? 'selected' : '' }}>
                        {{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Simpan</button>
        </form>
    </main>
</body>

</html>
