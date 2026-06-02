<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Dashboard Wali Kelas</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-wali.css') }}">
</head>

<body>

    @include('layouts.sidebar_wali')

    <div id="content" class="content">

        <div class="topbar">

            <h2>Dashboard Wali Kelas</h2>

            <br>

            <p>
                Selamat datang,
                <strong>{{ $user->nama }}</strong>
            </p>

            <p>
                Kelas Wali:
                <strong>{{ $wali->nama_kelas }}</strong>
            </p>

        </div>

        @include('layouts.libur_banner')

        <form method="GET" class="table-box"
            style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end">
            <label>Tahun Ajaran
                <select name="tahun_ajaran_id">
                    @foreach ($tahunAjaran as $ta)
                        <option value="{{ $ta->id }}"
                            {{ (string) $tahunAjaranId === (string) $ta->id ? 'selected' : '' }}>{{ $ta->nama }} -
                            {{ ucfirst($ta->semester) }}</option>
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
            <button class="btn">Terapkan</button>
            <a class="btn" target="_blank"
                href="/dashboard/wali/laporan-bulanan?bulan={{ now()->format('Y-m') }}&tahun_ajaran_id={{ $tahunAjaranId }}">Laporan
                Bulanan</a>
        </form>

        <div class="cards">

            <div class="card">
                <h3>Total Siswa</h3>
                <p>{{ count($siswa) }}</p>
            </div>

            <div class="card">
                <h3>Kelas</h3>
                <p>{{ $wali->nama_kelas }}</p>
            </div>

        </div>

        <div class="cards">
            <div class="card">
                <h3>Hadir 30 Hari</h3>
                <p>{{ (int) ($analitik->hadir ?? 0) }}</p>
            </div>
            <div class="card">
                <h3>Telat</h3>
                <p>{{ (int) ($analitik->telat ?? 0) }}</p>
            </div>
            <div class="card">
                <h3>Izin</h3>
                <p>{{ (int) ($analitik->izin ?? 0) }}</p>
            </div>
            <div class="card">
                <h3>Sakit</h3>
                <p>{{ (int) ($analitik->sakit ?? 0) }}</p>
            </div>
            <div class="card">
                <h3>Alfa</h3>
                <p>{{ (int) ($analitik->alfa ?? 0) }}</p>
            </div>
        </div>

        <div class="table-box">
            <h3>Analitik Kehadiran Kelas</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
                <div>
                    <h4>Tren Mingguan</h4>
                    @foreach ($trenMingguan as $t)
                        <div style="margin:8px 0">
                            <strong>Pekan {{ $t->pekan }}</strong>
                            <div style="height:12px;background:#e5e7eb;border-radius:999px;overflow:hidden">
                                <span
                                    style="display:block;height:12px;width:{{ min(100, $t->total * 4) }}%;background:#2563eb"></span>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div>
                    <h4>Siswa Sering Telat/Alfa</h4>
                    <table>
                        <tr>
                            <th>Nama</th>
                            <th>Total</th>
                            <th>Surat</th>
                        </tr>
                        @forelse($topRawan as $r)
                            <tr>
                                <td>{{ $r->nama }}</td>
                                <td>{{ $r->total }}</td>
                                <td><a href="/dashboard/wali/surat/{{ $r->id }}" target="_blank">Cetak</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">Tidak ada siswa rawan.</td>
                            </tr>
                        @endforelse
                    </table>
                </div>
            </div>
        </div>

        <div class="table-box">

            <h3>Data Siswa</h3>

            <table>

                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Kelas</th>
                    <th>Nama Orang Tua</th>
                    <th>No Orang Tua</th>
                    <th>Status</th>
                </tr>

                @foreach ($siswa as $s)
                    <tr>

                        <td>{{ $s->nama }}</td>

                        <td>{{ $s->username }}</td>

                        <td>{{ $s->nama_kelas ?? '-' }}</td>

                        <td>{{ $s->nama_ortu ?? '-' }}</td>

                        <td>{{ $s->no_ortu }}</td>

                        <td>
                            @if ($s->status_hari_ini == 'hadir')
                                <span class="badge" class="metric-green">
                                    Hadir
                                </span>
                            @elseif($s->status_hari_ini == 'telat')
                                <span class="badge" class="metric-orange">
                                    Telat
                                </span>
                            @else
                                <span class="badge" class="metric-red">
                                    Belum Absen
                                </span>
                            @endif
                        </td>

                    </tr>
                @endforeach

            </table>

        </div>

    </div>

</body>

</html>
