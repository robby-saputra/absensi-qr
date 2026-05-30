<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Absensi Siswa</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-wali_absensi.css') }}">
</head>
<body>

@include('layouts.sidebar_wali')

<div id="content" class="content">

    <div class="topbar">

        <h2>Absensi Siswa</h2>

        <br>

        <p>
            Kelas:
            <strong>{{ $wali->nama_kelas }}</strong>
        </p>
        <p>
            <a href="/dashboard/wali/pdf/absensi?tanggal={{ $tanggal }}" target="_blank">PDF Resmi</a>
        </p>

    </div>

    <div class="table-box">
        <form method="GET" class="filter-box">
            <label>Tanggal <input type="date" name="tanggal" value="{{ $tanggal }}"></label>
            <label>Bulan <input type="number" name="bulan" min="1" max="12" value="{{ $bulan }}"></label>
            <label>Tahun <input type="number" name="tahun" min="2020" max="2100" value="{{ $tahun }}"></label>
            <label>Status
                <select name="status">
                    <option value="">Semua</option>
                    @foreach(['hadir','telat','izin','sakit','alfa','alpa'] as $opsi)
                        <option value="{{ $opsi }}" {{ $status === $opsi ? 'selected' : '' }}>{{ ucfirst($opsi) }}</option>
                    @endforeach
                </select>
            </label>
            <label>Tahun Ajaran
                <select name="tahun_ajaran_id">
                    @foreach($tahunAjaran as $ta)
                        <option value="{{ $ta->id }}" {{ (string)$tahunAjaranId === (string)$ta->id ? 'selected' : '' }}>{{ $ta->nama }} - {{ ucfirst($ta->semester) }}</option>
                    @endforeach
                </select>
            </label>
            <label>Semester
                <select name="semester">
                    <option value="">Semua Semester</option>
                    <option value="ganjil" {{ $semesterFilter === 'ganjil' ? 'selected' : '' }}>Ganjil</option>
                    <option value="genap" {{ $semesterFilter === 'genap' ? 'selected' : '' }}>Genap</option>
                </select>
            </label>
            <button type="submit">Tampilkan</button>
            <a href="/dashboard/wali/absensi">Reset</a>
        </form>

        <div class="cards">
            <div class="card"><h3>Hadir</h3><p>{{ $ringkasan['hadir'] ?? 0 }}</p></div>
            <div class="card"><h3>Telat</h3><p>{{ $ringkasan['telat'] ?? 0 }}</p></div>
            <div class="card"><h3>Izin</h3><p>{{ $ringkasan['izin'] ?? 0 }}</p></div>
            <div class="card"><h3>Sakit</h3><p>{{ $ringkasan['sakit'] ?? 0 }}</p></div>
            <div class="card"><h3>Alfa</h3><p>{{ $ringkasan['alfa'] ?? 0 }}</p></div>
        </div>

        <h3>Siswa Perlu Perhatian 30 Hari Terakhir</h3>
        <table>
            <tr><th>Nama</th><th>Total Temuan</th><th>Aksi</th></tr>
            @forelse($siswaRawan as $r)
                <tr>
                    <td>{{ $r->nama }}</td>
                    <td>{{ $r->total_temuan }}</td>
                    <td><a href="/dashboard/wali/siswa/detail/{{ $r->id }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="3">Tidak ada siswa rawan pada periode ini.</td></tr>
            @endforelse
        </table>

        <h3>Rekap Absensi</h3>
        <table>

            <tr>
                <th>Nama</th>
                <th>Tanggal</th>
                <th>Jam Masuk</th>
                <th>Status Masuk</th>
                <th>Jam Pulang</th>
                <th>Status Pulang</th>
            </tr>

            @foreach($absensi as $a)

                <tr>
                    <td>{{ $a->nama }}</td>
                    <td>{{ $a->tanggal }}</td>
                    <td>{{ $a->jam_masuk }}</td>
                    <td>{{ $a->status_masuk }}</td>
                    <td>{{ $a->jam_pulang ?? '-' }}</td>
                    <td>{{ $a->status_pulang ?? '-' }}</td>
                </tr>

            @endforeach

        </table>

    </div>

</div>

</body>
</html>



