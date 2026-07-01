{{-- File ini menampilkan dashboard guru sebagai halaman utama guru dalam mengakses fitur absensi dan informasi jadwal. --}}
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
                    @php
                        $rekapTitle = $activeGuruPage === 'rekap_jadwal' ? 'Rekap Jadwal Guru Mapel' : 'Rekap Absensi Mapel Guru';
                        $rekapPdfType = $activeGuruPage === 'rekap_jadwal' ? 'jadwal' : 'absensi-mapel';
                        $exportParams = $activeGuruPage === 'rekap_jadwal'
                            ? array_filter([
                                'rekap_hari' => $rekapHariFilter,
                                'rekap_jadwal_id' => $rekapJpFilter,
                                'rekap_kelas_id' => $rekapKelasFilter,
                                'rekap_mapel_id' => $rekapMapelFilter,
                                'rekap_peran' => $rekapPeranFilter,
                                'rekap_tahun_ajaran_id' => $rekapTahunAjaranFilter,
                            ], fn ($value) => $value !== null && $value !== '')
                            : array_filter([
                                'tanggal' => $tanggalFilter,
                                'jadwal_id' => $jadwalFilter,
                                'kelas_id' => $kelasFilter,
                                'jurusan_id' => $jurusanFilter,
                                'status_harian' => $statusHarianFilter,
                                'tahun_ajaran_id' => $tahunAjaranId,
                            ], fn ($value) => $value !== null && $value !== '');
                        $exportQuery = http_build_query($exportParams);
                    @endphp
                    <button type="button" class="btn" onclick="printReport('{{ $rekapTitle }}')">Print Rekap</button>
                    <button type="button" class="btn btn-success"
                        onclick="exportTableToExcel('rekap-guru-mapel', '{{ $rekapTitle }}')">Excel</button>
                    <a class="btn btn-purple" target="_blank"
                        href="/dashboard/guru/pdf/{{ $rekapPdfType }}?{{ $exportQuery }}">PDF
                        Resmi</a>
                    <a class="btn btn-orange" target="_blank"
                        href="/dashboard/guru/laporan-bulanan?type={{ $rekapPdfType }}&bulan={{ now()->format('Y-m') }}&{{ $exportQuery }}">Laporan
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

        @if(($tugasPiketAktif ?? null) && $tugasPiketAktif->role !== 'utama')
            <section class="attendance-panel" style="border-left:5px solid #f59e0b">
                <div class="section-head">
                    <div>
                        <span class="section-kicker">Tugas Guru Piket Hari Ini</span>
                        <h3>Anda bertugas menggantikan {{ $tugasPiketAktif->primary_name }}</h3>
                        @if($tugasPiketAktif->previous_replacement_name)
                            <p>Pengganti sebelumnya <strong>{{ $tugasPiketAktif->previous_replacement_name }}</strong> berhalangan.</p>
                        @endif
                        <p>{{ \Carbon\Carbon::parse($tugasPiketAktif->date)->locale('id')->translatedFormat('l, d F Y') }} · {{ substr($tugasPiketAktif->schedule->jam_mulai,0,5) }}–{{ substr($tugasPiketAktif->schedule->jam_selesai,0,5) }}</p>
                    </div>
                    <div class="verify-date"><span>Status</span><strong>{{ ucfirst(str_replace('_',' ',$tugasPiketAktif->status)) }}</strong></div>
                </div>
                @if($tugasPiketAktif->status === 'belum_konfirmasi' && !($isPastDutyCutoff ?? false))
                    <p>Silakan konfirmasi kondisi Anda paling lambat pukul 07.00 WIB.</p>
                    <form method="POST" action="/dashboard/piket/status" class="status-action-form">
                        @csrf
                        <button class="btn btn-success" name="status" value="hadir">Hadir</button>
                        <button class="btn btn-orange" name="status" value="izin">Izin</button>
                        <button class="btn" name="status" value="sakit">Sakit</button>
                    </form>
                @elseif(($isPastDutyCutoff ?? false) && $tugasPiketAktif->status === 'hadir' && ($tugasPiketAktif->daily_status?->sumber ?? null) === 'system_cutoff')
                    <div class="alert success">Status Anda otomatis ditetapkan Hadir karena batas konfirmasi pukul 07.00 WIB telah lewat.</div>
                @endif
                <a class="btn btn-purple" href="/dashboard/piket">Buka Dashboard Guru Piket</a>
            </section>
        @endif

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
                                <td>{{ labelJadwalJp($jadwalStatus) }}</td>
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

        @if ($activeGuruPage === 'kalender_mengajar')
            <section class="attendance-panel">
                <div class="section-head">
                    <div>
                        <h3>Kalender Mengajar</h3>
                        <p>Jadwal mingguan sebagai guru utama maupun guru pengganti.</p>
                    </div>
                    <div class="verify-date">
                        <span>Total Jadwal</span>
                        <strong>{{ $semuaJadwalGuru->count() }}</strong>
                    </div>
                </div>

                <div class="teaching-calendar">
                    @foreach ($hariKalenderMengajar as $hariKalender)
                        @php
                            $jadwalHari = $semuaJadwalGuru
                                ->filter(fn ($item) => \Illuminate\Support\Str::lower((string) $item->hari) === \Illuminate\Support\Str::lower($hariKalender))
                                ->sortBy('jam_mulai')
                                ->values();
                        @endphp

                        <section class="calendar-day">
                            <div class="calendar-day-head">
                                <h4>{{ $hariKalender }}</h4>
                                <span>{{ $jadwalHari->count() }} sesi</span>
                            </div>

                            @forelse ($jadwalHari as $item)
                                @php
                                    $roleMengajar = $item->role_mengajar ?? 'guru_utama';
                                    $statusGuru = $item->status_guru ?: 'normal';
                                    $penggantiAktif = $roleMengajar === 'guru_pengganti' && in_array($statusGuru, ['izin', 'sakit', 'inval', 'digantikan']);
                                    $penggantiBertugas = $penggantiAktif && ($item->pengganti_status ?? null) === 'bertugas';
                                    $roleClass = $roleMengajar === 'guru_pengganti' ? 'status-pengganti' : 'status-normal';
                                    $roleLabel = $roleMengajar === 'guru_pengganti' ? 'Guru Pengganti' : 'Guru Utama';
                                @endphp

                                <article class="calendar-session">
                                    <div>
                                        <strong>{{ $item->nama_mapel }}</strong>
                                        <span>{{ $item->nama_kelas }}{{ !empty($item->nama_jurusan) ? ' - '.$item->nama_jurusan : '' }}</span>
                                    </div>
                                    <div class="calendar-session-meta">
                                        <span>{{ labelJadwalJp($item) }}</span>
                                        <span class="status {{ $roleClass }}">{{ $roleLabel }}</span>
                                    </div>
                                    @if ($roleMengajar === 'guru_pengganti')
                                        <small>
                                            {{ $penggantiBertugas ? 'Aktif bertugas saat guru utama tidak hadir.' : ($penggantiAktif ? 'Menunggu konfirmasi bertugas.' : 'Menunggu status guru utama.') }}
                                        </small>
                                    @elseif ($statusGuru !== 'normal')
                                        <small>Status hari terpilih: {{ ucfirst($statusGuru) }}</small>
                                    @endif
                                </article>
                            @empty
                                <div class="empty-state compact">Tidak ada jadwal.</div>
                            @endforelse
                        </section>
                    @endforeach
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
                                {{ ($tugas->jenis ?? '') === 'Guru Mapel' ? labelJadwalJp($tugas) : \Illuminate\Support\Str::of($tugas->jam_mulai)->substr(0, 5).' - '.\Illuminate\Support\Str::of($tugas->jam_selesai)->substr(0, 5) }}
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

                            {{ labelJadwalJp($j) }}

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

                @php
                    $jpAktifId = (string) ($jadwalFilter ?: optional($jadwalMapelFilterOptions->firstWhere('sedang_berjalan', true))->id ?: optional($jadwalMapelFilterOptions->first())->id);
                    $jpAktif = $jadwalMapelFilterOptions->first(fn ($opsi) => (string) $opsi->id === $jpAktifId);
                @endphp

                @if($jadwalMapelFilterOptions->isNotEmpty())
                    <div class="jp-verification-notice" id="jpVerificationNotice">
                        <div class="jp-notice-icon">JP</div>
                        <div>
                            <span>{{ !empty($jpAktif->sedang_berjalan) ? 'Sesi sedang berlangsung' : 'Sesi verifikasi terpilih' }}</span>
                            <strong id="activeJpTitle">{{ $jpAktif ? labelJadwalJp($jpAktif) : '-' }}</strong>
                            <small id="activeJpDetail">{{ $jpAktif ? $jpAktif->nama_mapel.' · '.$jpAktif->nama_kelas : '' }}</small>
                        </div>
                    </div>

                    <nav class="jp-session-tabs" aria-label="Pilih sesi jam pelajaran">
                        @foreach($jadwalMapelFilterOptions as $opsiJadwal)
                            <button type="button"
                                class="jp-session-tab {{ (string) $opsiJadwal->id === $jpAktifId ? 'is-active' : '' }} {{ !empty($opsiJadwal->sedang_berjalan) ? 'is-running' : '' }}"
                                data-jadwal-id="{{ $opsiJadwal->id }}"
                                data-title="{{ labelJadwalJp($opsiJadwal) }}"
                                data-detail="{{ $opsiJadwal->nama_mapel }} · {{ $opsiJadwal->nama_kelas }}"
                                data-running="{{ !empty($opsiJadwal->sedang_berjalan) ? '1' : '0' }}">
                                <span>{{ labelJadwalJp($opsiJadwal, false) }}</span>
                                <strong>{{ $opsiJadwal->nama_mapel }}</strong>
                                <small>{{ $opsiJadwal->nama_kelas }} · {{ substr($opsiJadwal->jam_mulai, 0, 5) }}–{{ substr($opsiJadwal->jam_selesai, 0, 5) }}</small>
                                @if(!empty($opsiJadwal->sedang_berjalan))<b>Sedang berjalan</b>@endif
                            </button>
                        @endforeach
                    </nav>
                @else
                    <div class="jp-verification-notice is-empty">
                        <div class="jp-notice-icon">JP</div>
                        <div><strong>Tidak ada sesi JP pada tanggal ini</strong><small>Pilih tanggal lain untuk melihat sesi verifikasi.</small></div>
                    </div>
                @endif

                @php
                    $jpItemsAktif = $absensiMapelKelasAjar->where('jadwal_id', $jpAktifId);
                    $hitungStatJp = function ($items) {
                        return [
                            'hadir' => $items->filter(fn ($row) => $row->jam_harian_masuk && !in_array($row->status_harian_masuk, ['izin','sakit','alfa','alpa']))->count(),
                            'telat' => $items->filter(fn ($row) => in_array(strtolower((string) $row->status_harian_masuk), ['telat','terlambat']))->count(),
                            'izin' => $items->filter(fn ($row) => in_array('izin', [$row->status_harian_masuk, $row->status_harian_pulang]))->count(),
                            'sakit' => $items->filter(fn ($row) => in_array('sakit', [$row->status_harian_masuk, $row->status_harian_pulang]))->count(),
                            'alfa' => $items->filter(fn ($row) => in_array($row->status_harian_masuk, ['alfa','alpa']) || in_array($row->status_harian_pulang, ['alfa','alpa']) || !$row->jam_harian_masuk)->count(),
                            'belum' => $items->whereNull('absensi_mapel_id')->count(),
                            'nonaktif' => (int) optional($items->first())->siswa_nonaktif_kelas,
                        ];
                    };
                    $jpStatsAktif = $hitungStatJp($jpItemsAktif);
                @endphp
                <div class="verify-stats">
                    <div class="verify-stat stat-hadir">
                        <span>Hadir</span><strong data-jp-stat="hadir">{{ $jpStatsAktif['hadir'] }}</strong></div>
                    <div class="verify-stat stat-telat">
                        <span>Telat</span><strong data-jp-stat="telat">{{ $jpStatsAktif['telat'] }}</strong></div>
                    <div class="verify-stat stat-izin">
                        <span>Izin</span><strong data-jp-stat="izin">{{ $jpStatsAktif['izin'] }}</strong></div>
                    <div class="verify-stat stat-sakit">
                        <span>Sakit</span><strong data-jp-stat="sakit">{{ $jpStatsAktif['sakit'] }}</strong></div>
                    <div class="verify-stat stat-alfa">
                        <span>Alfa</span><strong data-jp-stat="alfa">{{ $jpStatsAktif['alfa'] }}</strong></div>
                    <div class="verify-stat stat-belum"><span>Belum
                            Mapel</span><strong data-jp-stat="belum">{{ $jpStatsAktif['belum'] }}</strong></div>
                    <div class="verify-stat stat-sakit">
                        <span>Siswa Nonaktif</span><strong data-jp-stat="nonaktif">{{ $jpStatsAktif['nonaktif'] }}</strong></div>
                </div>

                @forelse($absensiMapelKelasAjar->groupBy('jadwal_id') as $jadwalGroupId => $items)
                    @php
                        $jadwalGroup = $items->first();
                        $namaKelas = ($jadwalGroup->nama_kelas ?? 'Tanpa Kelas').' - '.$jadwalGroup->nama_mapel;
                        $statGroup = $hitungStatJp($items);
                    @endphp
                    <div class="verify-class-card jp-session-content" data-jadwal-id="{{ $jadwalGroupId }}"
                        data-stats='@json($statGroup)'
                        @if((string) $jadwalGroupId !== $jpAktifId) hidden @endif>
                        <div class="class-title-row">
                            <div>
                                <span class="class-jp-label">{{ labelJadwalJp($jadwalGroup) }}</span>
                                <h4 class="class-title">{{ $namaKelas }}</h4>
                            </div>
                            <span>{{ $items->count() }} siswa</span>
                        </div>
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
                                            !empty($a->sesi_sedang_berjalan) &&
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
                                        <td>{{ labelJadwalJp($a) }}</td>
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
                                            @elseif(empty($a->sesi_sedang_berjalan))
                                                <button class="btn disabled" disabled>Di Luar Jam</button>
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
                <div class="empty-state" id="jpSessionEmpty" hidden>Tidak ada data siswa pada sesi JP ini.</div>
            </section>

            <script>
                (() => {
                    const tabs = [...document.querySelectorAll('.jp-session-tab')];
                    const contents = [...document.querySelectorAll('.jp-session-content')];
                    const title = document.getElementById('activeJpTitle');
                    const detail = document.getElementById('activeJpDetail');
                    const notice = document.getElementById('jpVerificationNotice');
                    const empty = document.getElementById('jpSessionEmpty');

                    tabs.forEach(tab => tab.addEventListener('click', () => {
                        const id = tab.dataset.jadwalId;
                        let visible = 0;
                        tabs.forEach(item => item.classList.toggle('is-active', item === tab));
                        contents.forEach(content => {
                            const match = content.dataset.jadwalId === id;
                            content.hidden = !match;
                            if (match) visible++;
                        });
                        if (title) title.textContent = tab.dataset.title;
                        if (detail) detail.textContent = tab.dataset.detail;
                        if (notice) notice.classList.toggle('is-running', tab.dataset.running === '1');
                        const activeContent = contents.find(content => content.dataset.jadwalId === id);
                        const stats = activeContent ? JSON.parse(activeContent.dataset.stats || '{}') : {};
                        document.querySelectorAll('[data-jp-stat]').forEach(item => {
                            item.textContent = stats[item.dataset.jpStat] ?? 0;
                        });
                        if (empty) empty.hidden = visible !== 0;
                    }));
                })();
            </script>
        @endif

        @if (in_array($activeGuruPage, ['dashboard', 'riwayat']))
            @php
                $riwayatMapel = collect($riwayatAbsensiMapelGuru ?? []);
                $riwayatHadir = $riwayatMapel->filter(fn ($row) => in_array(strtolower((string) $row->status), ['hadir','masuk','tepat waktu']))->count();
                $siswaPerluPerhatian = $riwayatMapel->groupBy('siswa_id')->map(function ($items) {
                    $terlambat = $items
                        ->filter(fn ($row) => in_array(strtolower((string) $row->status), ['telat', 'terlambat']))
                        ->sortBy('tanggal')
                        ->unique('tanggal')
                        ->values();

                    foreach ($terlambat as $akhir) {
                        $akhirTanggal = \Carbon\Carbon::parse($akhir->tanggal);
                        $awalTanggal = $akhirTanggal->copy()->subDays(2);
                        $dalamTigaHari = $terlambat->filter(function ($row) use ($awalTanggal, $akhirTanggal) {
                            $tanggal = \Carbon\Carbon::parse($row->tanggal);
                            return $tanggal->betweenIncluded($awalTanggal, $akhirTanggal);
                        });

                        if ($dalamTigaHari->count() >= 3) {
                            $terbaru = $items->sortByDesc('tanggal')->first();
                            $terbaru->tanggal_telat = $dalamTigaHari->pluck('tanggal')->sort()->values();
                            return $terbaru;
                        }
                    }

                    return null;
                })->filter()->values();
            @endphp
            <section class="attendance-panel report-panel" id="riwayat-absensi">
                <div class="section-head">
                    <div>
                        <span class="section-kicker">Riwayat Sesi JP</span>
                        <h3>Riwayat Absensi Mapel</h3>
                        <p>Data lengkap 30 hari terakhir dari sesi Anda sebagai guru utama maupun guru pengganti.</p>
                    </div>
                    <div class="verify-date"><span>Total data</span><strong>{{ $riwayatMapel->count() }}</strong></div>
                </div>

                <div class="history-summary">
                    <div><span>Total Rekaman</span><strong>{{ $riwayatMapel->count() }}</strong></div>
                    <div><span>Tercatat Hadir</span><strong>{{ $riwayatHadir }}</strong></div>
                    <div class="attention-summary"><span>Perlu Perhatian</span><strong>{{ $siswaPerluPerhatian->count() }}</strong>
                        <button type="button" id="showAttentionStudents" {{ $siswaPerluPerhatian->isEmpty() ? 'disabled' : '' }}>Lihat</button>
                    </div>
                    <div><span>Sesi JP</span><strong>{{ $riwayatMapel->pluck('jadwal_id')->unique()->count() }}</strong></div>
                </div>

                <div class="attention-panel" id="attentionStudents" hidden>
                    <div class="attention-panel-head">
                        <div><span>Siswa perlu perhatian</span><strong>Telat 3 kali dalam rentang 3 hari</strong></div>
                        <button type="button" id="closeAttentionStudents">Tutup</button>
                    </div>
                    @forelse($siswaPerluPerhatian as $siswaPerhatian)
                        <div class="attention-student">
                            <div class="avatar">{{ strtoupper(substr($siswaPerhatian->nama, 0, 2)) }}</div>
                            <div>
                                <strong>{{ $siswaPerhatian->nama }}</strong>
                                <span>{{ $siswaPerhatian->nis ?? '-' }} · {{ $siswaPerhatian->nama_kelas }}</span>
                                <small>Telat: {{ $siswaPerhatian->tanggal_telat->map(fn ($tanggal) => \Carbon\Carbon::parse($tanggal)->format('d/m/Y'))->implode(', ') }}</small>
                            </div>
                            <a class="btn btn-purple" href="/dashboard/guru/absensi-mapel/{{ $siswaPerhatian->jadwal_id }}/{{ $siswaPerhatian->siswa_id }}/view?tanggal={{ $siswaPerhatian->tanggal }}">Lihat Siswa</a>
                        </div>
                    @empty
                        <div class="empty-state compact">Tidak ada siswa yang memenuhi indikator perhatian.</div>
                    @endforelse
                </div>

                @forelse($riwayatMapel->groupBy(fn ($row) => $row->tanggal.'|'.$row->jadwal_id) as $groupKey => $items)
                    @php $sesi = $items->first(); @endphp
                    <article class="history-session-card">
                        <header>
                            <div>
                                <span class="history-date">{{ \Carbon\Carbon::parse($sesi->tanggal)->locale('id')->translatedFormat('l, d F Y') }}</span>
                                <h4>{{ $sesi->nama_mapel }} · {{ $sesi->nama_kelas }}</h4>
                                <small>{{ $sesi->nama_jurusan ?? 'Tanpa jurusan' }}</small>
                            </div>
                            <div class="history-session-meta">
                                <strong>{{ labelJadwalJp($sesi, false) }}</strong>
                                <span>{{ substr($sesi->jam_mulai, 0, 5) }}–{{ substr($sesi->jam_selesai, 0, 5) }}</span>
                                <b>{{ $sesi->role_mengajar === 'guru_pengganti' ? 'Guru Pengganti' : 'Guru Utama' }}</b>
                            </div>
                        </header>
                        <div class="verify-table-wrap">
                            <table class="verify-table history-table">
                                <tr><th>Siswa</th><th>NIS</th><th>Status Harian</th><th>Jam Scan Mapel</th><th>Status Mapel</th><th>Catatan</th></tr>
                                @foreach($items as $r)
                                    @php
                                        $statusHarianRiwayat = $r->status_harian_masuk ?: ($r->jam_harian_masuk ? 'hadir' : 'belum tercatat');
                                        $statusMapelKey = strtolower((string) ($r->status ?? ''));
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $r->nama }}</strong></td>
                                        <td>{{ $r->nis ?? '-' }}</td>
                                        <td>{{ $r->jam_harian_masuk ? substr($r->jam_harian_masuk, 0, 5).' · ' : '' }}{{ ucfirst($statusHarianRiwayat) }}</td>
                                        <td>{{ $r->jam_scan ? substr($r->jam_scan, 0, 5) : '-' }}</td>
                                        <td><span class="status {{ in_array($statusMapelKey, ['hadir','masuk','tepat waktu']) ? 'status-normal' : 'status-ganti' }}">{{ $r->status ?? 'Belum tercatat' }}</span></td>
                                        <td>{{ $r->catatan_guru ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </article>
                @empty
                    <div class="empty-state">Belum ada riwayat absensi mapel dari sesi JP Anda.</div>
                @endforelse
            </section>
            <script>
                (() => {
                    const open = document.getElementById('showAttentionStudents');
                    const close = document.getElementById('closeAttentionStudents');
                    const panel = document.getElementById('attentionStudents');
                    open?.addEventListener('click', () => { panel.hidden = false; panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); });
                    close?.addEventListener('click', () => { panel.hidden = true; });
                })();
            </script>
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
            @php
                $rekapRows = collect($rekapAbsensiMapelGuru);
                $rekapTelat = $rekapRows->filter(fn ($row) => in_array(\Illuminate\Support\Str::lower((string) ($row->status_harian_masuk ?? '')), ['telat', 'terlambat']))->count();
                $rekapTidakHadir = $rekapRows->filter(function ($row) {
                    $masuk = \Illuminate\Support\Str::lower((string) ($row->status_harian_masuk ?? ''));
                    $pulang = \Illuminate\Support\Str::lower((string) ($row->status_harian_pulang ?? ''));
                    return in_array($masuk, ['izin', 'sakit', 'alfa', 'alpa']) || in_array($pulang, ['izin', 'sakit', 'alfa', 'alpa']) || empty($row->jam_harian_masuk);
                })->count();
                $rekapMapelTerisi = $rekapRows->filter(fn ($row) => !empty($row->absensi_mapel_id))->count();
            @endphp
            <section class="attendance-panel report-panel">
                <div class="section-head report-head">
                    <div>
                        <h3>Rekap Absensi Mapel</h3>
                        <p>Gabungan status harian dan absensi sesi mapel untuk guru utama maupun guru pengganti.</p>
                    </div>
                    <div class="report-date-chip">{{ \Carbon\Carbon::parse($tanggalFilter)->locale('id')->translatedFormat('d F Y') }}</div>
                </div>

                <form method="GET" action="/dashboard/guru/rekap-absensi-mapel" class="filter-box report-filter-box">
                    <label>
                        Tanggal Laporan
                        <input type="date" name="tanggal" value="{{ $tanggalFilter }}">
                    </label>
                    <label>
                        Sesi JP / Mapel
                        <select name="jadwal_id">
                            <option value="">Semua sesi JP</option>
                            @foreach($jadwalMapelFilterOptions as $opsiJadwal)
                                <option value="{{ $opsiJadwal->id }}" {{ (string) $jadwalFilter === (string) $opsiJadwal->id ? 'selected' : '' }}>
                                    {{ labelJadwalJp($opsiJadwal, false) }} · {{ $opsiJadwal->nama_mapel }} · {{ $opsiJadwal->nama_kelas }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        Kelas
                        <select name="kelas_id">
                            <option value="">Semua kelas</option>
                            @foreach($kelasAjar as $k)
                                <option value="{{ $k->id }}" {{ (string) $kelasFilter === (string) $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        Jurusan
                        <select name="jurusan_id">
                            <option value="">Semua jurusan</option>
                            @foreach($jurusan as $jrs)
                                <option value="{{ $jrs->id }}" {{ (string) $jurusanFilter === (string) $jrs->id ? 'selected' : '' }}>{{ $jrs->nama_jurusan }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        Status Harian
                        <select name="status_harian">
                            <option value="">Semua status</option>
                            <option value="hadir" {{ $statusHarianFilter === 'hadir' ? 'selected' : '' }}>Hadir / Telat</option>
                            <option value="izin" {{ $statusHarianFilter === 'izin' ? 'selected' : '' }}>Izin</option>
                            <option value="sakit" {{ $statusHarianFilter === 'sakit' ? 'selected' : '' }}>Sakit</option>
                            <option value="alfa" {{ $statusHarianFilter === 'alfa' ? 'selected' : '' }}>Alfa</option>
                        </select>
                    </label>
                    <label>
                        Tahun Ajaran
                        <select name="tahun_ajaran_id">
                            @foreach($tahunAjaran as $ta)
                                <option value="{{ $ta->id }}" {{ (string) $tahunAjaranId === (string) $ta->id ? 'selected' : '' }}>{{ $ta->nama }} · {{ ucfirst($ta->semester) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="filter-actions">
                        <button type="submit" class="btn">Terapkan</button>
                        <a href="/dashboard/guru/rekap-absensi-mapel" class="btn disabled">Reset</a>
                    </div>
                </form>

                <div class="report-metrics">
                    <div><span>Total Data</span><strong>{{ $rekapRows->count() }}</strong></div>
                    <div><span>Absen Mapel Terisi</span><strong>{{ $rekapMapelTerisi }}</strong></div>
                    <div><span>Telat</span><strong>{{ $rekapTelat }}</strong></div>
                    <div><span>Tidak Hadir</span><strong>{{ $rekapTidakHadir }}</strong></div>
                </div>

                <table id="rekap-guru-mapel" class="export-only">
                    <tr>
                        <th>Tanggal</th>
                        <th>Siswa</th>
                        <th>NIS</th>
                        <th>Kelas</th>
                        <th>Mapel</th>
                        <th>Jam Pelajaran</th>
                        <th>Status Harian</th>
                        <th>Jam Scan Mapel</th>
                        <th>Status Mapel</th>
                        <th>Catatan</th>
                    </tr>
                    @foreach($rekapRows as $a)
                        @php
                            $statusExport = $a->status_harian_masuk ?: ($a->jam_harian_masuk ? 'hadir' : 'alfa');
                        @endphp
                        <tr>
                            <td>{{ $a->tanggal ?? $tanggalFilter }}</td>
                            <td>{{ $a->nama_siswa ?? $a->nama }}</td>
                            <td>{{ $a->nis ?? '-' }}</td>
                            <td>{{ $a->nama_kelas ?? '-' }}</td>
                            <td>{{ $a->nama_mapel }}</td>
                            <td>{{ labelJadwalJp($a) }}</td>
                            <td>{{ $a->jam_harian_masuk ? $a->jam_harian_masuk.' - ' : '' }}{{ $statusExport }}</td>
                            <td>{{ $a->jam_scan ?? '-' }}</td>
                            <td>{{ $a->status ?? 'belum absen mapel' }}</td>
                            <td>{{ $a->catatan_guru ?? '-' }}</td>
                        </tr>
                    @endforeach
                </table>

                @forelse($rekapRows->groupBy(fn ($item) => ($item->nama_kelas ?? 'Tanpa Kelas').' - '.$item->nama_mapel.' - '.\Illuminate\Support\Str::of($item->jam_mulai)->substr(0, 5)) as $groupLabel => $items)
                    <div class="report-card">
                        <div class="report-card-head">
                            <h4>{{ $groupLabel }}</h4>
                            <span>{{ labelJadwalJp($items->first()) }}</span>
                        </div>
                        <div class="modern-table-wrap">
                            <table class="modern-table">
                                <tr>
                                    <th>Siswa</th>
                                    <th>Status Harian</th>
                                    <th>Absen Mapel</th>
                                    <th>Jam Scan</th>
                                    <th>Catatan</th>
                                </tr>
                                @foreach($items as $a)
                                    @php
                                        $masuk = \Illuminate\Support\Str::lower((string) ($a->status_harian_masuk ?? ''));
                                        $pulang = \Illuminate\Support\Str::lower((string) ($a->status_harian_pulang ?? ''));
                                        $statusHarian = $a->status_harian_masuk ?: ($a->jam_harian_masuk ? 'hadir' : 'alfa');
                                        $statusClass = match (true) {
                                            in_array($masuk, ['telat', 'terlambat']) => 'report-badge danger',
                                            in_array($masuk, ['izin', 'sakit', 'alfa', 'alpa']) || in_array($pulang, ['izin', 'sakit', 'alfa', 'alpa']) || empty($a->jam_harian_masuk) => 'report-badge warning',
                                            default => 'report-badge success',
                                        };
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $a->nama_siswa ?? $a->nama }}</strong><br><small>{{ $a->nis ?? '-' }}</small></td>
                                        <td><span class="{{ $statusClass }}">{{ $a->jam_harian_masuk ? $a->jam_harian_masuk.' - ' : '' }}{{ $statusHarian }}</span></td>
                                        <td><span class="report-badge {{ !empty($a->absensi_mapel_id) ? 'success' : 'neutral' }}">{{ $a->status ?? 'belum absen mapel' }}</span></td>
                                        <td>{{ $a->jam_scan ?? '-' }}</td>
                                        <td>{{ $a->catatan_guru ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">Belum ada data rekap absensi mapel.</div>
                @endforelse
            </section>
        @endif

        @if ($activeGuruPage === 'rekap_jadwal')
            @php
                $jadwalRows = collect($rekapJadwalGuru);
                $jadwalUtama = $jadwalRows->where('role_mengajar', 'guru_utama')->count();
                $jadwalPengganti = $jadwalRows->where('role_mengajar', 'guru_pengganti')->count();
            @endphp
            <section class="attendance-panel report-panel">
                <div class="section-head report-head">
                    <div>
                        <h3>Rekap Jadwal Mengajar</h3>
                        <p>Seluruh jadwal sebagai guru utama dan guru pengganti.</p>
                    </div>
                </div>

                <form method="GET" action="/dashboard/guru/rekap-jadwal" class="filter-box report-filter-box schedule-filter-box">
                    <label>Hari
                        <select name="rekap_hari"><option value="">Semua hari</option>
                            @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $opsiHari)
                                <option value="{{ $opsiHari }}" {{ $rekapHariFilter === $opsiHari ? 'selected' : '' }}>{{ $opsiHari }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Sesi JP
                        <select name="rekap_jadwal_id"><option value="">Semua JP</option>
                            @foreach($semuaJadwalGuru as $opsiJadwal)
                                <option value="{{ $opsiJadwal->id }}" {{ (string) $rekapJpFilter === (string) $opsiJadwal->id ? 'selected' : '' }}>{{ labelJadwalJp($opsiJadwal, false) }} · {{ $opsiJadwal->nama_mapel }} · {{ $opsiJadwal->nama_kelas }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Kelas
                        <select name="rekap_kelas_id"><option value="">Semua kelas</option>
                            @foreach($kelasAjar as $k)<option value="{{ $k->id }}" {{ (string) $rekapKelasFilter === (string) $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>@endforeach
                        </select>
                    </label>
                    <label>Mata Pelajaran
                        <select name="rekap_mapel_id"><option value="">Semua mapel</option>
                            @foreach($rekapMapelOptions as $mapelOpsi)<option value="{{ $mapelOpsi->id }}" {{ (string) $rekapMapelFilter === (string) $mapelOpsi->id ? 'selected' : '' }}>{{ $mapelOpsi->nama_mapel }}</option>@endforeach
                        </select>
                    </label>
                    <label>Peran Mengajar
                        <select name="rekap_peran"><option value="">Semua peran</option><option value="guru_utama" {{ $rekapPeranFilter === 'guru_utama' ? 'selected' : '' }}>Guru Utama</option><option value="guru_pengganti" {{ $rekapPeranFilter === 'guru_pengganti' ? 'selected' : '' }}>Guru Pengganti</option></select>
                    </label>
                    <label>Tahun Ajaran
                        <select name="rekap_tahun_ajaran_id">@foreach($tahunAjaran as $ta)<option value="{{ $ta->id }}" {{ (string) $rekapTahunAjaranFilter === (string) $ta->id ? 'selected' : '' }}>{{ $ta->nama }} · {{ ucfirst($ta->semester) }}</option>@endforeach</select>
                    </label>
                    <div class="filter-actions"><button class="btn" type="submit">Terapkan</button><a class="btn disabled" href="/dashboard/guru/rekap-jadwal">Reset</a></div>
                </form>

                <div class="report-metrics">
                    <div><span>Total Jadwal</span><strong>{{ $jadwalRows->count() }}</strong></div>
                    <div><span>Guru Utama</span><strong>{{ $jadwalUtama }}</strong></div>
                    <div><span>Guru Pengganti</span><strong>{{ $jadwalPengganti }}</strong></div>
                    <div><span>Kelas</span><strong>{{ $jadwalRows->pluck('kelas_id')->unique()->count() }}</strong></div>
                </div>

                <table id="rekap-guru-mapel" class="export-only">
                    <tr>
                        <th>Hari</th>
                        <th>Jam</th>
                        <th>Kelas</th>
                        <th>Jurusan</th>
                        <th>Mapel</th>
                        <th>Peran</th>
                        <th>Status</th>
                    </tr>
                    @foreach($jadwalRows as $j)
                        @php
                            $isPenggantiExport = ($j->role_mengajar ?? 'guru_utama') === 'guru_pengganti';
                        @endphp
                        <tr>
                            <td>{{ $j->hari }}</td>
                            <td>{{ labelJadwalJp($j) }}</td>
                            <td>{{ $j->nama_kelas }}</td>
                            <td>{{ $j->nama_jurusan ?? '-' }}</td>
                            <td>{{ $j->nama_mapel }}</td>
                            <td>{{ $isPenggantiExport ? 'Guru Pengganti' : 'Guru Utama' }}</td>
                            <td>{{ $j->status_guru ?: 'normal' }}</td>
                        </tr>
                    @endforeach
                </table>

                <div class="schedule-report-grid">
                    @forelse($jadwalRows->groupBy('hari') as $hariJadwal => $items)
                        <div class="report-card">
                            <div class="report-card-head">
                                <h4>{{ $hariJadwal }}</h4>
                                <span>{{ $items->count() }} sesi</span>
                            </div>
                            @foreach($items->sortBy('jam_mulai') as $j)
                                @php
                                    $isPengganti = ($j->role_mengajar ?? 'guru_utama') === 'guru_pengganti';
                                    $statusGuru = $j->status_guru ?: 'normal';
                                @endphp
                                <article class="schedule-report-item">
                                    <div>
                                        <strong>{{ $j->nama_mapel }}</strong>
                                        <span>{{ $j->nama_kelas }}{{ !empty($j->nama_jurusan) ? ' - '.$j->nama_jurusan : '' }}</span>
                                    </div>
                                    <div>
                                        <span>{{ labelJadwalJp($j) }}</span>
                                        <span class="report-badge {{ $isPengganti ? 'purple' : 'success' }}">{{ $isPengganti ? 'Guru Pengganti' : 'Guru Utama' }}</span>
                                    </div>
                                    <small>Status: {{ $isPengganti && $statusGuru !== 'normal' ? 'Guru utama '.ucfirst($statusGuru) : ucfirst($statusGuru) }}</small>
                                </article>
                            @endforeach
                        </div>
                    @empty
                        <div class="empty-state">Belum ada jadwal.</div>
                    @endforelse
                </div>
            </section>
        @endif

    </main>

</body>

</html>
