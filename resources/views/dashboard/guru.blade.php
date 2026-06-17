<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-guru.css') }}">

</head>

<body>

    @include('layouts.sidebar_guru')

    <main id="content" class="content" data-print-title="Rekap Guru Mapel"
        data-print-date="{{ now()->format('d-m-Y H:i') }}">

        <div class="top">

            <div>

                <h2>Dashboard Guru</h2>

                <p>{{ $user->nama }}</p>

                <p>Jadwal Hari:
                    {{ $hari }}
                </p>

            </div>



            <div>

                @if (str_starts_with($activeGuruPage, 'rekap'))
                    <button type="button" class="btn" onclick="printReport('Rekap Guru Mapel')">Print Rekap</button>
                    <button type="button" class="btn btn-success"
                        onclick="exportTableToExcel('rekap-guru-mapel', 'Rekap Guru Mapel')">Excel</button>
                    <a class="btn btn-purple" target="_blank"
                        href="/dashboard/guru/pdf/{{ $activeGuruPage === 'rekap_siswa' ? 'siswa' : ($activeGuruPage === 'rekap_jadwal' ? 'jadwal' : 'absensi-mapel') }}?tanggal={{ $tanggalFilter }}">PDF
                        Resmi</a>
                    <a class="btn btn-orange" target="_blank"
                        href="/dashboard/guru/laporan-bulanan?bulan={{ now()->format('Y-m') }}&tahun_ajaran_id={{ $tahunAjaranId }}">Laporan
                        Bulanan</a>
                @endif

                <a class="btn" href="/logout">

                    Logout

                </a>



                @if ($isWaliKelas)
                    <a class="btn btn-success" href="/dashboard/wali">

                        Dashboard Wali Kelas

                    </a>
                @endif

                @if (($punyaAksesGuruPiket ?? false) || $isGuruPiketHariIni || ($isGuruPiketPenggantiAktifHariIni ?? false))
                    <a class="btn btn-success" href="/dashboard/piket">

                        {{ ($isGuruPiketHariIni || ($isGuruPiketPenggantiAktifHariIni ?? false)) ? 'Dashboard Guru Piket Hari Ini' : 'Dashboard Guru Piket' }}

                    </a>
                @endif


            </div>

        </div>

        @include('layouts.alerts')

        @include('layouts.libur_banner')

        @if ($activeGuruPage === 'status_mengajar')
            <section class="attendance-panel">
                <div class="section-head">
                    <div>
                        <h3>Status Mengajar Hari Ini</h3>
                        <p>Ringkasan jadwal mengajar hari ini sebagai guru utama maupun guru pengganti.</p>
                    </div>
                    <div class="verify-date">
                        <span>{{ $hari }}</span>
                        <strong>{{ now()->locale('id')->translatedFormat('d F Y') }}</strong>
                    </div>
                </div>

                @if (now()->format('H:i') > '06:30')
                    <div class="alert success">Batas pilih status guru sudah lewat pukul 06.30. Jadwal yang belum dipilih dianggap hadir.</div>
                @else
                    <div class="alert success">Pilih status setiap jadwal sebelum pukul 06.30. Jika memilih izin atau sakit, guru pengganti akan menjadi guru bertugas.</div>
                @endif

                <div class="verify-table-wrap">
                    <table class="verify-table">
                        <tr>
                            <th>Mapel</th>
                            <th>Kelas</th>
                            <th>Jam</th>
                            <th>Peran Anda</th>
                            <th>Guru Utama</th>
                            <th>Guru Pengganti</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>

                        @forelse ($statusMengajarHariIni as $jadwalStatus)
                            @php
                                $statusGuru = $jadwalStatus->status_guru ?: 'normal';
                                $roleMengajar = $jadwalStatus->role_mengajar ?? 'guru_utama';
                                $penggantiAktif = $roleMengajar === 'guru_pengganti' && in_array($statusGuru, ['izin', 'sakit', 'inval', 'digantikan']);
                                $penggantiBertugas = $penggantiAktif && ($jadwalStatus->pengganti_status ?? null) === 'bertugas';
                                $penggantiTidakHadir = $penggantiAktif && ($jadwalStatus->pengganti_status ?? null) === 'tidak_hadir';
                                $statusSudahDipilih = !empty($jadwalStatus->status_dipilih_at);
                                $batasPilihStatusLewat = now()->format('H:i') > '06:30';
                                $bolehPilihStatus = $roleMengajar === 'guru_utama' && !$statusSudahDipilih && !$batasPilihStatusLewat;
                            @endphp
                            <tr>
                                <td>{{ $jadwalStatus->nama_mapel }}</td>
                                <td>{{ $jadwalStatus->nama_kelas }}</td>
                                <td>{{ \Illuminate\Support\Str::of($jadwalStatus->jam_mulai)->substr(0, 5) }} - {{ \Illuminate\Support\Str::of($jadwalStatus->jam_selesai)->substr(0, 5) }}</td>
                                <td>
                                    <span class="status {{ $roleMengajar === 'guru_utama' ? 'status-normal' : 'status-ganti' }}">
                                        {{ $roleMengajar === 'guru_utama' ? 'Guru Utama' : 'Guru Pengganti' }}
                                    </span>
                                </td>
                                <td>{{ $jadwalStatus->nama_guru_utama ?? '-' }}</td>
                                <td>{{ $jadwalStatus->nama_guru_pengganti ?? 'Belum diatur' }}</td>
                                <td>
                                    <span class="status {{ $statusGuru === 'normal' ? 'status-normal' : 'status-ganti' }}">
                                        {{ $roleMengajar === 'guru_pengganti' && $statusGuru !== 'normal' ? 'Guru Utama '.ucfirst($statusGuru) : ($statusGuru === 'normal' ? 'Hadir' : ucfirst($statusGuru)) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($roleMengajar === 'guru_pengganti' && $penggantiBertugas)
                                        Anda aktif menggantikan {{ $jadwalStatus->nama_guru_utama ?? 'guru utama' }}.
                                    @elseif ($roleMengajar === 'guru_pengganti' && $penggantiTidakHadir)
                                        Anda melaporkan tidak bisa hadir. Admin perlu mengatur pengganti lanjutan.
                                    @elseif ($roleMengajar === 'guru_pengganti' && $penggantiAktif)
                                        Guru utama berhalangan. Silakan konfirmasi apakah Anda bisa bertugas.
                                    @elseif ($roleMengajar === 'guru_pengganti')
                                        Menunggu guru utama memilih izin atau sakit.
                                    @elseif ($statusGuru === 'normal')
                                        Guru utama bertugas.
                                    @elseif ($jadwalStatus->nama_guru_pengganti)
                                        Digantikan oleh {{ $jadwalStatus->nama_guru_pengganti }}.
                                    @else
                                        Guru pengganti belum diatur.
                                    @endif
                                </td>
                                <td>
                                    @if ($bolehPilihStatus)
                                        <form method="POST" action="/dashboard/guru/jadwal/{{ $jadwalStatus->id }}/status-guru" class="inline-status-form">
                                            @csrf
                                            <button class="btn" type="submit" name="status_guru" value="normal">Hadir</button>
                                            <button class="btn warning" type="submit" name="status_guru" value="izin">Izin</button>
                                            <button class="btn danger" type="submit" name="status_guru" value="sakit">Sakit</button>
                                        </form>
                                    @elseif ($roleMengajar === 'guru_utama')
                                        <button class="btn disabled" disabled>
                                            {{ $statusSudahDipilih ? 'Status Dipilih' : 'Lewat Batas' }}
                                        </button>
                                    @elseif ($penggantiAktif && empty($jadwalStatus->pengganti_status))
                                        <form method="POST" action="/dashboard/guru/jadwal/{{ $jadwalStatus->id }}/status-guru-pengganti" class="inline-status-form">
                                            @csrf
                                            <button class="btn" type="submit" name="pengganti_status" value="bertugas">Saya Bertugas</button>
                                            <button class="btn danger" type="submit" name="pengganti_status" value="tidak_hadir">Tidak Bisa Hadir</button>
                                        </form>
                                    @else
                                        <button class="btn disabled" disabled>
                                            {{ $penggantiBertugas ? 'Anda Bertugas' : ($penggantiTidakHadir ? 'Menunggu Admin' : 'Menunggu Status') }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">Tidak ada jadwal mengajar hari ini.</td>
                            </tr>
                        @endforelse
                    </table>
                </div>
            </section>
        @endif

        @if ($activeGuruPage === 'dashboard')
        <section class="current-duty-panel">
            <div class="current-duty-head">
                <span>Tugas Saat Ini</span>
                <strong>{{ now()->format('H:i') }}</strong>
            </div>
            @if (($tugasSaatIni ?? collect())->isNotEmpty())
                <div class="current-duty-grid">
                    @foreach ($tugasSaatIni as $tugas)
                        <article class="current-duty-card">
                            <div>
                                <span class="current-duty-role">{{ $tugas->jenis }}</span>
                                <h3>{{ $tugas->detail }}</h3>
                            </div>
                            <div class="current-duty-time">
                                {{ \Illuminate\Support\Str::of($tugas->jam_mulai)->substr(0, 5) }} -
                                {{ \Illuminate\Support\Str::of($tugas->jam_selesai)->substr(0, 5) }}
                            </div>
                            <span class="current-duty-status">{{ $tugas->status }}</span>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="current-duty-empty">
                    Tidak ada tugas yang sedang berjalan pada jam ini.
                </div>
            @endif
        </section>
        @endif






        @if (in_array($activeGuruPage, ['dashboard', 'jadwal']))
            <table id="jadwal-hari-ini">

                <tr>

                    <th>Kelas</th>

                    <th>Hari</th>

                    <th>Mapel</th>

                    <th>Jam</th>

                    <th>Status</th>

                    <th>Aksi Guru</th>

                    <th>Fitur</th>

                </tr>





                @forelse($jadwal as $j)
                    @php
                        $roleMengajar = $j->role_mengajar ?? 'guru_utama';
                        $statusGuru = $j->status_guru ?: 'normal';
                        $statusSudahDipilih = !empty($j->status_dipilih_at);
                        $batasPilihStatusLewat = now()->format('H:i') > '06:30';
                        $penggantiAktif = $roleMengajar === 'guru_pengganti' && in_array($statusGuru, ['izin', 'sakit', 'inval', 'digantikan']);
                        $penggantiBertugas = $penggantiAktif && ($j->pengganti_status ?? null) === 'bertugas';
                        $penggantiTidakHadir = $penggantiAktif && ($j->pengganti_status ?? null) === 'tidak_hadir';
                        $bolehMulaiSesi = ($roleMengajar === 'guru_utama' && $statusGuru === 'normal') || $penggantiBertugas;
                    @endphp
                    <tr>



                        <td>

                            {{ $j->nama_kelas }}

                        </td>

                        <td>

                            {{ $j->hari }}

                        </td>




                        <td>

                            {{ $j->nama_mapel }}

                        </td>





                        <td>

                            {{ $j->jam_mulai }}

                            -

                            {{ $j->jam_selesai }}

                        </td>








                        <td>


                            @if($roleMengajar === 'guru_pengganti')
                                <div class="info">
                                    @if ($penggantiBertugas)
                                        Anda sudah konfirmasi bertugas menggantikan jadwal ini.
                                    @elseif ($penggantiTidakHadir)
                                        Anda sudah melaporkan tidak bisa hadir. Menunggu admin.
                                    @else
                                        {{ $penggantiAktif ? 'Guru utama berhalangan. Silakan konfirmasi di menu Status Mengajar.' : 'Anda terdaftar sebagai guru pengganti. Menunggu status guru utama.' }}
                                    @endif
                                </div>
                            @endif

                            @if($statusGuru == 'normal')
                                <span class="status status-normal">

                                    {{ $roleMengajar === 'guru_pengganti' ? 'Menunggu Guru Utama' : 'Hadir' }}

                                </span>
                            @else
                                <span class="status status-ganti">

                                    {{ $roleMengajar === 'guru_pengganti' ? 'Guru Utama ' . ucfirst($statusGuru) : ucfirst($statusGuru) }}

                                </span>

                                <br>

                                <small>

                                    {{ $roleMengajar === 'guru_pengganti' ? ($penggantiBertugas ? 'Anda menjadi guru pengganti.' : 'Butuh konfirmasi pengganti.') : $j->alasan_tidak_hadir }}

                                </small>
                            @endif


                        </td>










                        <td>


                            @if($roleMengajar === 'guru_utama' && ! $statusSudahDipilih && ! $batasPilihStatusLewat)
                                <div class="info">

                                    <form method="POST" action="/dashboard/guru/jadwal/{{ $j->id }}/status-guru" class="inline-status-form">
                                        @csrf
                                        <button class="btn" type="submit" name="status_guru" value="normal">Hadir</button>
                                        <button class="btn warning" type="submit" name="status_guru" value="izin">Izin</button>
                                        <button class="btn danger" type="submit" name="status_guru" value="sakit">Sakit</button>
                                    </form>

                                    <small>Pilih sebelum pukul 06.30.</small>

                                </div>
                            @elseif($roleMengajar === 'guru_utama' && $statusGuru === 'normal')
                                <button class="btn disabled" disabled>

                                    Sedang Bertugas

                                </button>

                                <div class="info">

                                    {{ $batasPilihStatusLewat && ! $statusSudahDipilih ? 'Lewat pukul 06.30, guru utama dinyatakan hadir.' : 'Guru utama sudah memilih hadir.' }}

                                </div>
                            @elseif($roleMengajar === 'guru_pengganti' && ! $penggantiAktif)
                                <button class="btn disabled" disabled>

                                    Menunggu Status Guru Utama

                                </button>

                                <div class="info">

                                    Anda akan bertugas jika guru utama memilih izin atau sakit.

                                </div>
                            @else
                                <button class="btn disabled" disabled>

                                    {{ $penggantiBertugas ? 'Anda Guru Bertugas' : ($penggantiTidakHadir ? 'Menunggu Admin' : 'Status Sudah Dipilih') }}

                                </button>



                                <div class="info">

                                    @if ($statusGuru == 'normal')
                                        Guru hadir
                                    @else
                                        Status guru utama:

                                        <b>

                                            {{ ucfirst($statusGuru) }}

                                        </b>

                                        @if ($j->alasan_tidak_hadir)
                                            <br>

                                            Alasan:

                                            <b>

                                                {{ $j->alasan_tidak_hadir }}

                                            </b>
                                        @endif
                                    @endif

                                </div>
                            @endif



                        </td>









                        <td>


                            {{-- SESI MAPEL --}}
                            @if(! $bolehMulaiSesi)
                                <button class="btn disabled" disabled>

                                    {{ $roleMengajar === 'guru_pengganti' ? 'Belum Bertugas' : 'Sesi Dinonaktifkan' }}

                                </button>



                                <div class="info">

                                    @if ($roleMengajar === 'guru_pengganti' && $penggantiAktif && empty($j->pengganti_status))
                                        Konfirmasi dahulu di menu Status Mengajar.
                                    @elseif ($roleMengajar === 'guru_pengganti' && $penggantiTidakHadir)
                                        Menunggu admin mengatur pengganti lanjutan.
                                    @else
                                        {{ $roleMengajar === 'guru_pengganti' ? 'Menunggu guru utama memilih izin/sakit.' : 'Sesi tidak aktif untuk status ini.' }}
                                    @endif

                                </div>






                                {{-- HADIR --}}
                            @else
                                <a class="btn" href="/dashboard/guru/mulai-sesi/{{ $j->id }}">

                                    Mulai Sesi

                                </a>



                                <div class="info">

                                    {{ $penggantiBertugas ? 'Anda menggantikan guru utama untuk sesi ini.' : 'Guru utama sedang bertugas' }}

                                </div>

                            @endif






                        </td>



                    </tr>

                @empty

                    <tr>

                        <td colspan="8">

                            Tidak ada jadwal hari ini

                        </td>

                    </tr>
                @endforelse



            </table>
        @endif

        @if (in_array($activeGuruPage, ['dashboard', 'verifikasi']))
            <section class="attendance-panel verify-panel" id="verifikasi-absensi">
                <div class="section-head verify-head">
                    <div>
                        <h3>Verifikasi Absen Mapel</h3>
                        <p>Data diambil dari scan QR sesi mata pelajaran. Tidak ada absen pulang/akhir di guru mapel.
                        </p>
                    </div>
                    <div class="verify-date">
                        <span>Tanggal aktif</span>
                        <strong>{{ \Carbon\Carbon::parse($tanggalFilter)->locale('id')->translatedFormat('d F Y') }}</strong>
                    </div>
                </div>

                <form method="GET" action="/dashboard/guru" class="filter-box verify-filter">
                    <label>
                        Tanggal
                        <input type="date" name="tanggal" value="{{ $tanggalFilter }}">
                    </label>

                    <label>
                        Hari
                        <select name="hari">
                            <option value="">Semua Hari</option>
                            @foreach (['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h)
                                <option value="{{ $h }}" {{ $hariFilter == $h ? 'selected' : '' }}>
                                    {{ $h }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Bulan
                        <select name="bulan">
                            <option value="">Semua Bulan</option>
                            @foreach (range(1, 12) as $b)
                                <option value="{{ $b }}"
                                    {{ (string) $bulanFilter === (string) $b ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(null, $b, 1)->locale('id')->translatedFormat('F') }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Tahun
                        <input type="number" name="tahun" min="2020" max="2100" value="{{ $tahunFilter }}"
                            placeholder="Semua tahun">
                    </label>

                    <label>
                        Kelas
                        <select name="kelas_id">
                            <option value="">Semua Kelas</option>
                            @foreach ($kelasAjar as $k)
                                <option value="{{ $k->id }}"
                                    {{ (string) $kelasFilter === (string) $k->id ? 'selected' : '' }}>
                                    {{ $k->nama_kelas }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Jurusan
                        <select name="jurusan_id">
                            <option value="">Semua Jurusan</option>
                            @foreach ($jurusan as $jrs)
                                <option value="{{ $jrs->id }}"
                                    {{ (string) $jurusanFilter === (string) $jrs->id ? 'selected' : '' }}>
                                    {{ $jrs->nama_jurusan }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Status Harian
                        <select name="status_harian">
                            <option value="">Semua Status</option>
                            <option value="hadir" {{ $statusHarianFilter == 'hadir' ? 'selected' : '' }}>Hadir/Telat
                            </option>
                            <option value="izin" {{ $statusHarianFilter == 'izin' ? 'selected' : '' }}>Izin</option>
                            <option value="sakit" {{ $statusHarianFilter == 'sakit' ? 'selected' : '' }}>Sakit
                            </option>
                            <option value="alfa" {{ $statusHarianFilter == 'alfa' ? 'selected' : '' }}>Alfa</option>
                        </select>
                    </label>

                    <label>
                        Tahun Ajaran
                        <select name="tahun_ajaran_id">
                            @foreach ($tahunAjaran as $ta)
                                <option value="{{ $ta->id }}"
                                    {{ (string) $tahunAjaranId === (string) $ta->id ? 'selected' : '' }}>
                                    {{ $ta->nama }} - {{ ucfirst($ta->semester) }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        Semester
                        <select name="semester">
                            <option value="">Semua Semester</option>
                            <option value="ganjil" {{ $semesterFilter === 'ganjil' ? 'selected' : '' }}>Ganjil
                            </option>
                            <option value="genap" {{ $semesterFilter === 'genap' ? 'selected' : '' }}>Genap</option>
                        </select>
                    </label>

                    <div class="filter-actions verify-actions">
                        <button type="submit" class="btn">Terapkan</button>
                        <a href="/dashboard/guru" class="btn disabled">Reset</a>
                    </div>
                </form>

                <div class="verify-stats">
                    <div class="verify-stat stat-hadir">
                        <span>Hadir</span><strong>{{ $ringkasanGuru['hadir'] ?? 0 }}</strong></div>
                    <div class="verify-stat stat-telat">
                        <span>Telat</span><strong>{{ $ringkasanGuru['telat'] ?? 0 }}</strong></div>
                    <div class="verify-stat stat-izin">
                        <span>Izin</span><strong>{{ $ringkasanGuru['izin'] ?? 0 }}</strong></div>
                    <div class="verify-stat stat-sakit">
                        <span>Sakit</span><strong>{{ $ringkasanGuru['sakit'] ?? 0 }}</strong></div>
                    <div class="verify-stat stat-alfa">
                        <span>Alfa</span><strong>{{ $ringkasanGuru['alfa'] ?? 0 }}</strong></div>
                    <div class="verify-stat stat-belum"><span>Belum
                            Mapel</span><strong>{{ $ringkasanGuru['mapel_belum'] ?? 0 }}</strong></div>
                    <div class="verify-stat stat-sakit">
                        <span>Siswa Nonaktif</span><strong>{{ $siswaNonaktifKelasAjarCount ?? 0 }}</strong></div>
                </div>

                @forelse($absensiMapelKelasAjar->groupBy(fn($item) => ($item->nama_kelas ?? 'Tanpa Kelas').' - '.$item->nama_mapel) as $namaKelas => $items)
                    <div class="verify-class-card">
                        <div class="class-title-row">
                            <h4 class="class-title">{{ $namaKelas ?? 'Tanpa Kelas' }}</h4>
                            <span>{{ $items->count() }} siswa</span>
                        </div>
                        @php $jadwalGroup = $items->first(); @endphp
                        @if (!empty($jadwalGroup->sesi_terkunci))
                            <div class="alert success">Absensi mapel sudah melewati batas edit pukul {{ jamKunciAbsensiLabel() }}. Data hanya
                                bisa dilihat oleh guru dan hanya admin yang dapat mengubahnya.</div>
                        @else
                            <div class="alert success">Absensi mapel masih bisa dikoreksi sampai pukul {{ jamKunciAbsensiLabel() }}. Setelah
                                itu data terkunci otomatis untuk guru.</div>
                        @endif
                        <div class="verify-table-wrap">
                            <table class="verify-table">
                                <tr>
                                    <th>Nama</th>
                                    <th>NIS</th>
                                    <th>Jurusan</th>
                                    <th>Absen Harian Piket</th>
                                    <th>Jam Pelajaran</th>
                                    <th>Jam Absen Mapel</th>
                                    <th>Status Absen Mapel</th>
                                    <th>Catatan</th>
                                    <th>Aksi</th>
                                </tr>

                                @foreach ($items as $a)
                                    @php
                                        $statusHarianKhusus = in_array($a->status_harian_masuk, [
                                            'izin',
                                            'sakit',
                                            'alfa',
                                            'alpa',
                                        ])
                                            ? $a->status_harian_masuk
                                            : (in_array($a->status_harian_pulang, ['izin', 'sakit', 'alfa', 'alpa'])
                                                ? $a->status_harian_pulang
                                                : null);
                                        $statusHarian =
                                            $statusHarianKhusus ??
                                            ($a->status_harian_masuk ?: ($a->jam_harian_masuk ? 'hadir' : 'alfa'));
                                        if (!empty($a->keterangan_libur) && $statusHarian === 'libur') {
                                            $statusHarian = 'libur';
                                        }
                                        $statusHarianKey = \Illuminate\Support\Str::lower((string) $statusHarian);
                                        $bolehEditMapel =
                                            $a->boleh_kelola_mapel &&
                                            empty($a->sesi_terkunci) &&
                                            !in_array($statusHarianKey, ['izin', 'sakit', 'alfa', 'alpa', 'libur']);
                                        $statusHarianClass = match ($statusHarianKey) {
                                            'hadir' => 'status-normal',
                                            'telat', 'terlambat' => 'status-ganti',
                                            'izin', 'sakit', 'alfa', 'alpa' => 'status-ganti',
                                            'libur' => 'status-belum',
                                            default => $bolehEditMapel ? 'status-normal' : 'status-belum',
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $a->nama }}</td>
                                        <td>{{ $a->nis ?? '-' }}</td>
                                        <td>{{ $a->nama_jurusan ?? '-' }}</td>
                                        <td>
                                            <span
                                                class="status {{ $statusHarianClass }}">
                                                {{ $a->jam_harian_masuk ? $a->jam_harian_masuk . ' - ' : '' }}{{ $statusHarian }}
                                                @if (!empty($a->keterangan_libur))
                                                    <br><small>{{ $a->keterangan_libur }}</small>
                                                @endif
                                            </span>
                                        </td>
                                        <td>{{ $a->jam_mulai }} - {{ $a->jam_selesai }}</td>
                                        <td>{{ $a->jam_scan ?? '-' }}</td>
                                        <td>
                                            <span class="status {{ $a->status ? 'status-normal' : 'status-belum' }}">
                                                {{ $a->status ?? 'belum absen mapel' }}
                                            </span>
                                        </td>
                                        <td>{{ $a->catatan_guru ?? '-' }}</td>
                                        <td>
                                            <a class="btn btn-success"
                                                href="/dashboard/guru/absensi-mapel/{{ $a->jadwal_id }}/{{ $a->siswa_id }}/view?tanggal={{ $tanggalFilter }}">View</a>
                                            @if ($bolehEditMapel)
                                                <a class="btn btn-purple"
                                                    href="/dashboard/guru/absensi-mapel/{{ $a->jadwal_id }}/{{ $a->siswa_id }}/edit?tanggal={{ $tanggalFilter }}">Edit</a>
                                            @else
                                                <button class="btn disabled" disabled>Edit Nonaktif</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">Tidak ada data siswa untuk filter yang dipilih.</div>
                @endforelse
            </section>
        @endif

        @if (in_array($activeGuruPage, ['dashboard', 'riwayat']))
            <section class="attendance-panel" id="riwayat-absensi">
                <div class="section-head">
                    <div>
                        <h3>Riwayat Absensi Siswa</h3>
                        <p>Riwayat 7 hari terakhir untuk siswa di kelas yang Anda ajar hari ini.</p>
                    </div>
                </div>

                @forelse($riwayatAbsensiKelasAjar->groupBy('nama_kelas') as $namaKelas => $items)
                    <h4 class="class-title">{{ $namaKelas ?? 'Tanpa Kelas' }}</h4>
                    <table>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama</th>
                            <th>Jurusan</th>
                            <th>Masuk</th>
                            <th>Status Masuk</th>
                            <th>Pulang</th>
                            <th>Status Pulang</th>
                        </tr>

                        @foreach ($items as $r)
                            <tr>
                                <td>{{ $r->tanggal }}</td>
                                <td>{{ $r->nama }}</td>
                                <td>{{ $r->nama_jurusan ?? '-' }}</td>
                                <td>{{ $r->jam_masuk ?? '-' }}</td>
                                <td>{{ $r->status_masuk ?? '-' }}</td>
                                <td>{{ $r->jam_pulang ?? '-' }}</td>
                                <td>{{ $r->status_pulang ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </table>
                @empty
                    <div class="empty-state">Belum ada riwayat absensi untuk filter yang dipilih.</div>
                @endforelse
            </section>
        @endif

        @if ($activeGuruPage === 'rekap_siswa')
            <section class="attendance-panel">
                <div class="section-head">
                    <div>
                        <h3>Rekap Siswa Kelas Ajar</h3>
                        <p>Daftar siswa dari semua kelas yang menjadi jadwal mengajar utama Anda.</p>
                    </div>
                </div>

                @forelse($rekapSiswaGuru->groupBy('nama_kelas') as $namaKelas => $items)
                    <h4 class="class-title">{{ $namaKelas ?? 'Tanpa Kelas' }}</h4>
                    <table>
                        <tr>
                            <th>Nama</th>
                            <th>NIS</th>
                            <th>Jurusan</th>
                            <th>Username</th>
                            <th>Nama Orang Tua</th>
                            <th>No Orang Tua</th>
                        </tr>
                        @foreach ($items as $s)
                            <tr>
                                <td>{{ $s->nama }}</td>
                                <td>{{ $s->nis ?? '-' }}</td>
                                <td>{{ $s->nama_jurusan ?? '-' }}</td>
                                <td>{{ $s->username }}</td>
                                <td>{{ $s->nama_ortu ?? '-' }}</td>
                                <td>{{ $s->no_ortu ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </table>
                @empty
                    <div class="empty-state">Belum ada siswa dari kelas ajar Anda.</div>
                @endforelse
            </section>
        @endif

        @if ($activeGuruPage === 'rekap_absensi')
            <section class="attendance-panel">
                <div class="section-head">
                    <div>
                        <h3>Rekap Absensi Harian</h3>
                        <p>Rekap absensi masuk/pulang dari siswa kelas ajar Anda.</p>
                    </div>
                </div>

                @forelse($riwayatAbsensiKelasAjar->groupBy('nama_kelas') as $namaKelas => $items)
                    <h4 class="class-title">{{ $namaKelas ?? 'Tanpa Kelas' }}</h4>
                    <table>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama</th>
                            <th>Masuk</th>
                            <th>Status Masuk</th>
                            <th>Pulang</th>
                            <th>Status Pulang</th>
                        </tr>
                        @foreach ($items as $r)
                            <tr>
                                <td>{{ $r->tanggal }}</td>
                                <td>{{ $r->nama }}</td>
                                <td>{{ $r->jam_masuk ?? '-' }}</td>
                                <td>{{ $r->status_masuk ?? '-' }}</td>
                                <td>{{ $r->jam_pulang ?? '-' }}</td>
                                <td>{{ $r->status_pulang ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </table>
                @empty
                    <div class="empty-state">Belum ada rekap absensi.</div>
                @endforelse
            </section>
        @endif

        @if ($activeGuruPage === 'rekap_absensi_mapel')
            <section class="attendance-panel">
                <div class="section-head">
                    <div>
                        <h3>Rekap Absensi Mapel</h3>
                        <p>Absensi siswa yang scan QR sesi mata pelajaran.</p>
                    </div>
                </div>

                <table>
                    <tr>
                        <th>Tanggal</th>
                        <th>Siswa</th>
                        <th>Kelas</th>
                        <th>Mapel</th>
                        <th>Jam Scan</th>
                        <th>Status</th>
                        <th>Catatan</th>
                    </tr>
                    @forelse($rekapAbsensiMapelGuru as $a)
                        <tr>
                            <td>{{ $a->tanggal }}</td>
                            <td>{{ $a->nama_siswa }}</td>
                            <td>{{ $a->nama_kelas ?? '-' }}</td>
                            <td>{{ $a->nama_mapel }}</td>
                            <td>{{ $a->jam_scan ?? '-' }}</td>
                            <td>{{ $a->status }}</td>
                            <td>{{ $a->catatan_guru ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">Belum ada absensi mapel.</td>
                        </tr>
                    @endforelse
                </table>
            </section>
        @endif

        @if ($activeGuruPage === 'rekap_jadwal')
            <section class="attendance-panel">
                <div class="section-head">
                    <div>
                        <h3>Rekap Jadwal Mengajar</h3>
                        <p>Jadwal mengajar utama Anda.</p>
                    </div>
                </div>

                <table>
                    <tr>
                        <th>Hari</th>
                        <th>Jam</th>
                        <th>Kelas</th>
                        <th>Mapel</th>
                        <th>Status</th>
                    </tr>
                    @forelse($semuaJadwalGuru as $j)
                        <tr>
                            <td>{{ $j->hari }}</td>
                            <td>{{ $j->jam_mulai }} - {{ $j->jam_selesai }}</td>
                            <td>{{ $j->nama_kelas }}</td>
                            <td>{{ $j->nama_mapel }}</td>
                            <td>{{ $j->status_guru ?? 'belum dipilih' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">Belum ada jadwal.</td>
                        </tr>
                    @endforelse
                </table>
            </section>
        @endif

    </main>

</body>

</html>
