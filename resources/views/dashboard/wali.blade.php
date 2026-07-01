{{-- File ini menampilkan dashboard wali kelas untuk memantau data siswa, absensi, dan surat terkait kelasnya. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Wali Kelas</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-wali.css') }}">
</head>

<body>

    @include('layouts.sidebar_wali')

    <div id="content" class="content">
        @include('layouts.alerts')

        <div class="topbar hero-card">

            <div>
                <span class="eyebrow">Ruang Wali Kelas</span>
                <h2>Dashboard Wali Kelas</h2>
                <p>Selamat datang, <strong>{{ $user->nama }}</strong></p>
            </div>

            <div class="hero-class-pill">
                <span>Kelas Wali</span>
                <strong>{{ $wali->nama_kelas }}</strong>
            </div>

        </div>

        @include('layouts.libur_banner')

        <form method="GET" class="filter-card">
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

        <div class="cards stat-grid">

            <div class="card">
                <span class="card-icon blue">AK</span>
                <h3>Siswa Aktif</h3>
                <p>{{ count($siswa) }}</p>
            </div>

            <div class="card">
                <span class="card-icon red">NA</span>
                <h3>Siswa Nonaktif</h3>
                <p>{{ $siswaNonaktifCount ?? 0 }}</p>
                <a href="/dashboard/wali/siswa?status=nonaktif">Lihat</a>
            </div>

            <div class="card">
                <span class="card-icon teal">KL</span>
                <h3>Kelas</h3>
                <p>{{ $wali->nama_kelas }}</p>
            </div>

        </div>

        <div class="cards attendance-grid">
            <div class="card">
                <span class="card-icon green">H</span>
                <h3>Hadir 30 Hari</h3>
                <p>{{ (int) ($analitik->hadir ?? 0) }}</p>
            </div>
            <div class="card">
                <span class="card-icon orange">T</span>
                <h3>Telat</h3>
                <p>{{ (int) ($analitik->telat ?? 0) }}</p>
            </div>
            <div class="card">
                <span class="card-icon blue">I</span>
                <h3>Izin</h3>
                <p>{{ (int) ($analitik->izin ?? 0) }}</p>
            </div>
            <div class="card">
                <span class="card-icon purple">S</span>
                <h3>Sakit</h3>
                <p>{{ (int) ($analitik->sakit ?? 0) }}</p>
            </div>
            <div class="card">
                <span class="card-icon red">A</span>
                <h3>Alfa</h3>
                <p>{{ (int) ($analitik->alfa ?? 0) }}</p>
            </div>
        </div>

        <div class="table-box analytics-card">
            <div class="section-head">
                <span class="eyebrow dark">Monitoring</span>
                <h3>Analitik Kehadiran Kelas</h3>
            </div>
            <div class="analytics-grid">
                <div>
                    <h4>Tren Mingguan</h4>
                    @foreach ($trenMingguan as $t)
                        <div class="trend-row">
                            <strong>Pekan {{ $t->pekan }}</strong>
                            <div class="trend-bar">
                                <span style="width:{{ min(100, $t->total * 4) }}%"></span>
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

        <div class="table-box data-card">

            <div class="section-head">
                <span class="eyebrow dark">Daftar Siswa</span>
                <h3>Data Siswa</h3>
            </div>

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
                                <span class="badge metric-green">
                                    Hadir
                                </span>
                            @elseif($s->status_hari_ini == 'telat')
                                <span class="badge metric-orange">
                                    Telat
                                </span>
                            @else
                                <span class="badge metric-red">
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
