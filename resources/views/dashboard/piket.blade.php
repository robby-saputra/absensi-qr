{{-- File ini menampilkan dashboard guru piket untuk memantau absensi, izin, dan kegiatan kehadiran harian. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru Piket</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-piket.css') }}">
</head>

<body>
    @include('layouts.sidebar_piket')

    <main id="content" class="content" data-print-title="Rekap Guru Piket"
        data-print-date="{{ now()->format('d-m-Y H:i') }}">
        <div class="page-head">
            <div>
                <h2>Dashboard Guru Piket</h2>
                <p>{{ $user->nama }} - {{ now()->locale('id')->translatedFormat('l, d F Y') }}</p>
            </div>
            @if (in_array($activePiketPage, ['absensi', 'riwayat', 'jadwal']))
                <div>
                    <button type="button" class="btn" onclick="printReport('Rekap Guru Piket')">Print Rekap</button>
                    <button type="button" class="btn"
                        onclick="exportTableToExcel('rekap-guru-piket', 'Rekap Guru Piket')">Excel</button>
                    <a class="btn" target="_blank"
                        href="/dashboard/piket/pdf/{{ $activePiketPage === 'jadwal' ? 'jadwal' : 'absensi' }}?tanggal={{ $tanggalFilter }}">PDF
                        Resmi</a>
                    <a class="btn" target="_blank"
                        href="/dashboard/piket/laporan-bulanan?bulan={{ now()->format('Y-m') }}&tahun_ajaran_id={{ $tahunAjaranId }}">Laporan
                        Bulanan</a>
                </div>
            @endif
        </div>

        @if (session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif

        @include('layouts.libur_banner')

        @if (($user->role ?? null) === 'guru' && $jadwalPiketHariIni)
            <section class="card piket-status-card">
                <div class="piket-status-head">
                    <div>
                        <span class="section-kicker">Status Kehadiran Guru Piket</span>
                        <h3>{{ ($isGuruPiketPengganti ?? false) ? 'Status guru pengganti piket' : 'Konfirmasi tugas piket hari ini' }}</h3>
                        <p class="muted">
                            Jadwal {{ ucfirst($jadwalPiketHariIni->hari) }},
                            {{ substr($jadwalPiketHariIni->jam_mulai, 0, 5) }} -
                            {{ substr($jadwalPiketHariIni->jam_selesai, 0, 5) }}.
                        </p>
                    </div>
                    <span class="piket-status-pill">
                        {{ ($isGuruPiketPengganti ?? false) ? (($guruPiketPenggantiAktif ?? false) ? 'Anda Bertugas' : 'Menunggu Guru Utama') : ($jadwalPiketHariIni->status ?? 'Belum Dipilih') }}
                    </span>
                </div>

                @if (($isGuruPiketPengganti ?? false) && ($guruPiketPenggantiAktif ?? false))
                    <div class="alert success">
                        Anda bertugas menggantikan <strong>{{ $namaGuruUtamaDigantikan ?? 'guru piket utama' }}</strong>
                        @if($namaPenggantiSebelumnya ?? false)<br>Pengganti sebelumnya <strong>{{ $namaPenggantiSebelumnya }}</strong> berhalangan.@endif
                        pada {{ now()->locale('id')->translatedFormat('l, d F Y') }}. Konfirmasikan kondisi Anda sendiri
                        sebelum mengelola QR tim.
                    </div>
                    @if ($hasConfirmedPiketToday ?? false)
                        <div class="alert success">
                            Status Anda sebagai guru piket pengganti sudah dipilih dan dikunci. Jika ada perubahan,
                            hubungi admin.
                        </div>
                    @elseif (!($isPastDutyCutoff ?? false))
                        <p class="muted">Silakan konfirmasi kondisi Anda paling lambat pukul 07.00 WIB.</p>
                        <form method="POST" action="/dashboard/piket/status" class="status-action-form">
                            @csrf
                            <button class="btn" type="submit" name="status" value="hadir"
                                data-confirm="Konfirmasi hadir sebagai guru piket pengganti hari ini?">Hadir</button>
                            <button class="btn status-izin" type="submit" name="status" value="izin"
                                data-confirm="Konfirmasi izin sebagai guru piket pengganti hari ini?">Izin</button>
                            <button class="btn status-sakit" type="submit" name="status" value="sakit"
                                data-confirm="Konfirmasi sakit sebagai guru piket pengganti hari ini?">Sakit</button>
                        </form>
                    @else
                        <div class="alert success">Status yang belum dipilih otomatis ditetapkan Hadir karena batas konfirmasi pukul 07.00 WIB telah lewat.</div>
                    @endif
                @elseif (($isGuruPiketPengganti ?? false))
                    <div class="alert success">
                        Anda terdaftar sebagai guru pengganti, tetapi belum memiliki tugas penggantian hari ini.
                        Tugas baru aktif jika {{ $namaGuruUtamaDigantikan ?? 'guru utama' }} memilih izin atau sakit hari ini.
                    </div>
                @elseif ($hasConfirmedPiketToday ?? false)
                    <div class="alert success">
                        Status sudah dipilih dan dikunci. Jika ada perubahan mendadak, hubungi admin untuk validasi
                        jadwal.
                    </div>
                @elseif (!($isPastDutyCutoff ?? false))
                    <p class="muted">Silakan konfirmasi kondisi Anda paling lambat pukul 07.00 WIB.</p>
                    <form method="POST" action="/dashboard/piket/status" class="status-action-form">
                        @csrf
                        <button class="btn" type="submit" name="status" value="hadir"
                            data-confirm="Konfirmasi hadir sebagai guru piket hari ini? Status akan dikunci.">Hadir</button>
                        <button class="btn status-izin" type="submit" name="status" value="izin"
                            data-confirm="Konfirmasi izin sebagai guru piket hari ini? Status akan dikunci.">Izin</button>
                        <button class="btn status-sakit" type="submit" name="status" value="sakit"
                            data-confirm="Konfirmasi sakit sebagai guru piket hari ini? Status akan dikunci.">Sakit</button>
                    </form>
                @else
                    <div class="alert success">Status yang belum dipilih otomatis ditetapkan Hadir karena batas konfirmasi pukul 07.00 WIB telah lewat.</div>
                @endif
            </section>
        @endif

        @if (in_array($activePiketPage, ['dashboard', 'qr']))
            @if (!($bolehKelolaQrPiket ?? true))
                <section class="card piket-status-card">
                    <span class="section-kicker">QR Dinonaktifkan</span>
                    <h3>{{ ($isGuruPiketPengganti ?? false) ? 'Menunggu status guru utama' : 'Anda tercatat tidak hadir sebagai guru piket' }}</h3>
                    <p class="muted">
                        {{ ($isGuruPiketPengganti ?? false)
                            ? 'QR absensi harian baru aktif untuk guru pengganti jika guru utama memilih izin atau sakit.'
                            : 'QR absensi harian tidak ditampilkan dan tidak bisa digenerate untuk akun ini. Anda tetap bisa membuka monitoring absensi siswa, riwayat, dan rekap jadwal.' }}
                    </p>
                </section>
            @else
                <section class="qr-team-layout">
                <div class="card qr-control-card">
                    <span class="section-kicker">QR Absensi Harian</span>
                    <h3>1 QR untuk 1 Tim Piket</h3>
                    <p class="muted">QR ini mewakili jadwal tim piket hari ini. Guru dalam tim yang sama cukup memakai
                        QR yang sama untuk absensi masuk atau pulang.</p>

                    @if (($anggotaTimPiket ?? collect())->isNotEmpty())
                        <div class="team-strip">
                            <div>
                                <span>Tim Hari Ini</span>
                                <strong>{{ ucfirst($anggotaTimPiket->first()->hari) }}</strong>
                            </div>
                            <div>
                                <span>Jam</span>
                                <strong>{{ substr($anggotaTimPiket->first()->jam_mulai, 0, 5) }} -
                                    {{ substr($anggotaTimPiket->first()->jam_selesai, 0, 5) }}</strong>
                            </div>
                            <div>
                                <span>Anggota</span>
                                <strong>{{ $anggotaTimPiket->count() }} guru</strong>
                            </div>
                        </div>

                        <div class="team-member-mini">
                            @foreach ($anggotaTimPiket as $anggota)
                                @php
                                    $inisial = collect(explode(' ', trim($anggota->guru_utama)))
                                        ->filter()
                                        ->take(2)
                                        ->map(fn($nama) => strtoupper(substr($nama, 0, 1)))
                                        ->implode('');
                                @endphp
                                <div class="mini-person">
                                    <span>{{ $inisial ?: 'GP' }}</span>
                                    <strong>{{ $anggota->guru_utama }}</strong>
                                </div>
                            @endforeach
                        </div>

                    @else
                        <div class="alert error">Tim guru piket hari ini belum ditemukan. Admin perlu mengatur jadwal
                            piket hari ini terlebih dahulu.</div>
                    @endif

                    <form method="POST" action="/dashboard/piket/generate-qr" class="qr-generate-form">
                        @csrf
                        <label>
                            Tipe Absensi
                            <select name="tipe">
                                <option value="masuk" {{ ($tipe ?? 'masuk') == 'masuk' ? 'selected' : '' }}>Masuk
                                </option>
                                <option value="pulang" {{ ($tipe ?? 'masuk') == 'pulang' ? 'selected' : '' }}>Pulang
                                </option>
                            </select>
                        </label>
                        <button class="btn" type="submit"
                            {{ ($anggotaTimPiket ?? collect())->isEmpty() ? 'disabled' : '' }}>Generate QR Tim</button>
                    </form>
                </div>

                <div class="card qr-display-card">
                    <div class="qr-display-head">
                        <div>
                            <span class="section-kicker">QR Aktif</span>
                            <h3>{{ $qr ? 'QR Tim Piket ' . ucfirst($qr->tipe) : 'Belum Ada QR' }}</h3>
                        </div>
                        @if ($qr)
                            <span class="qr-type">{{ ucfirst($qr->tipe) }}</span>
                        @endif
                    </div>

                    @if ($qr)
                        <div class="qr-box qr-box-modern">
                            <img src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(300)->margin(2)->generate($qr->token)) }}"
                                alt="QR Code">
                        </div>
                        <a class="btn view-qr-btn" href="/dashboard/piket/qr/{{ $qr->id }}/view"
                            target="_blank">View QR Besar</a>
                        <div class="qr-meta">
                            <div><span>Token</span><strong>{{ $qr->token }}</strong></div>
                            <div><span>Berlaku
                                    Sampai</span><strong>{{ $qr->expires_at ? \Carbon\Carbon::parse($qr->expires_at)->format('H:i') : '-' }}</strong>
                            </div>
                            <div><span>Dibuat
                                    Oleh</span><strong>{{ $qr->generated_by ? (DB::table('users')->where('id', $qr->generated_by)->value('nama') ?: '-') : '-' }}</strong>
                            </div>
                        </div>
                    @else
                        <div class="empty-qr">
                            <strong>QR belum dibuat</strong>
                            <span>Pilih tipe absensi, lalu generate QR tim.</span>
                        </div>
                    @endif
                </div>
                </section>
            @endif
        @endif

        @if (in_array($activePiketPage, ['dashboard', 'absensi']))
            <section class="card">
                <h3>Absensi Harian Siswa</h3>
                <p class="muted">Data absen masuk dan pulang harian yang dipakai guru mapel untuk melihat kehadiran
                    siswa di kelas ajarnya.</p>

                <section class="grid">
                    <div class="card">
                        <h3>Belum Masuk</h3>
                        <h2>{{ $belumAbsenMasuk ?? 0 }}</h2>
                    </div>
                    <div class="card">
                        <h3>Belum Pulang</h3>
                        <h2>{{ $belumAbsenPulang ?? 0 }}</h2>
                    </div>
                    <div class="card">
                        <h3>Batas Edit</h3>
                        <h2>{{ $absensiHarianTerkunci ? 'Terkunci' : 'Sebelum ' . jamKunciAbsensiLabel() }}</h2>
                    </div>
                </section>

                <form method="GET" class="filter-box">
                    <input type="hidden" name="page" value="{{ $activePiketPage }}">
                    <label>Tanggal <input type="date" name="tanggal" value="{{ $tanggalFilter }}"></label>
                    <label>Kelas
                        <select name="kelas_id">
                            <option value="">Semua Kelas</option>
                            @foreach ($kelas as $k)
                                <option value="{{ $k->id }}"
                                    {{ (string) $kelasFilter === (string) $k->id ? 'selected' : '' }}>
                                    {{ $k->nama_kelas }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Tahun Ajaran
                        <select name="tahun_ajaran_id">
                            @foreach ($tahunAjaran as $ta)
                                <option value="{{ $ta->id }}"
                                    {{ (string) $tahunAjaranId === (string) $ta->id ? 'selected' : '' }}>
                                    {{ $ta->nama }} - {{ ucfirst($ta->semester) }}</option>
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
                    <button class="btn" type="submit">Tampilkan</button>
                    <a class="btn muted-btn" href="/dashboard/piket?page={{ $activePiketPage }}">Reset</a>
                </form>

                @if ($absensiHarianTerkunci)
                    <div class="alert success">Absensi harian sudah melewati batas edit pukul {{ jamKunciAbsensiLabel() }}. Data hanya bisa
                        dilihat oleh guru/piket dan hanya admin yang dapat mengubahnya.</div>
                @else
                    <div class="alert success">Absensi harian masih bisa dikoreksi sampai pukul {{ jamKunciAbsensiLabel() }}. Setelah itu
                        data terkunci otomatis untuk guru/piket.</div>
                @endif

                <div class="table-wrap">
                    <table>
                        <tr>
                            <th>Nama</th>
                            <th>NIS</th>
                            <th>Kelas</th>
                            <th>Absen Harian Masuk</th>
                            <th>Absen Harian Pulang</th>
                            <th>Catatan</th>
                            <th>Aksi</th>
                        </tr>
                        @forelse($absensiSiswa as $a)
                            @php
                                $khusus = in_array($a->status_masuk, ['izin', 'sakit'])
                                    ? $a->status_masuk
                                    : (in_array($a->status_pulang, ['izin', 'sakit'])
                                        ? $a->status_pulang
                                        : null);
                                $masuk = $khusus ?? $a->status_masuk;
                                $pulang = $khusus ?? $a->status_pulang;
                                $masukKey = \Illuminate\Support\Str::lower((string) $masuk);
                                $pulangKey = \Illuminate\Support\Str::lower((string) $pulang);
                                $statusBadgeClass = function ($status) {
                                    return match ($status) {
                                        'hadir' => 'status-normal',
                                        'telat', 'terlambat', 'izin', 'sakit', 'alfa', 'alpa' => 'status-ganti',
                                        default => 'status-belum',
                                    };
                                };
                            @endphp
                            <tr>
                                <td>{{ $a->nama }}</td>
                                <td>{{ $a->nis ?? '-' }}</td>
                                <td>{{ $a->nama_kelas ?? '-' }}</td>
                                <td>
                                    @if ($masuk || $a->jam_masuk)
                                        <span class="status {{ $statusBadgeClass($masukKey ?: 'hadir') }}">
                                            {{ $a->jam_masuk ? $a->jam_masuk . ' - ' : '' }}{{ $masuk ?: 'hadir' }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($pulang || $a->jam_pulang)
                                        <span class="status {{ $statusBadgeClass($pulangKey ?: 'hadir') }}">
                                            {{ $a->jam_pulang ? $a->jam_pulang . ' - ' : '' }}{{ $pulang ?: 'hadir' }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $a->catatan_piket ?? '-' }}</td>
                                <td>
                                    <a class="btn"
                                        href="/dashboard/piket/absensi/{{ $a->id }}/view?tanggal={{ $tanggalFilter }}">View</a>
                                    @if ($absensiHarianTerkunci)
                                        <button class="btn muted-btn" disabled>Edit Terkunci</button>
                                    @else
                                        <a class="btn muted-btn"
                                            href="/dashboard/piket/absensi/{{ $a->id }}/edit?tanggal={{ $tanggalFilter }}">Edit</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">Belum ada data siswa.</td>
                            </tr>
                        @endforelse
                    </table>
                </div>
            </section>
        @endif

        @if ($activePiketPage === 'dashboard')
            <section class="grid">
                <div class="card">
                    <h3>Total Siswa</h3>
                    <h2>{{ $totalSiswa }}</h2>
                </div>
                <div class="card">
                    <h3>Siswa Nonaktif</h3>
                    <h2>{{ $totalSiswaNonaktif ?? 0 }}</h2>
                </div>
                <div class="card">
                    <h3>QR Aktif</h3>
                    <h2>{{ ($bolehKelolaQrPiket ?? true) ? ($qr ? '1' : '0') : 'Nonaktif' }}</h2>
                </div>
                <div class="card">
                    <h3>Status</h3>
                    <h2 class="text-success">Aktif</h2>
                </div>
            </section>
        @endif

        @if ($activePiketPage === 'riwayat')
            <section class="card">
                <h3>Riwayat Absensi Harian</h3>
                <div class="table-wrap">
                    <table>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama</th>
                            <th>NIS</th>
                            <th>Kelas</th>
                            <th>Masuk</th>
                            <th>Pulang</th>
                        </tr>
                        @forelse($riwayatAbsensi as $r)
                            <tr>
                                <td>{{ $r->tanggal }}</td>
                                <td>{{ $r->nama }}</td>
                                <td>{{ $r->nis ?? '-' }}</td>
                                <td>{{ $r->nama_kelas ?? '-' }}</td>
                                <td>{{ $r->status_masuk ? ($r->jam_masuk ? $r->jam_masuk . ' - ' : '') . $r->status_masuk : '-' }}
                                </td>
                                <td>{{ $r->status_pulang ? ($r->jam_pulang ? $r->jam_pulang . ' - ' : '') . $r->status_pulang : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">Belum ada riwayat.</td>
                            </tr>
                        @endforelse
                    </table>
                </div>
            </section>
        @endif

        @if ($activePiketPage === 'jadwal')
            <section class="card">
                <h3>Rekap Jadwal Guru Piket</h3>
                <div class="table-wrap">
                    <table>
                        <tr>
                            <th>Guru</th>
                            <th>Hari</th>
                            <th>Jam</th>
                            <th>Status</th>
                        </tr>
                        @foreach ($rekapJadwalPiket as $j)
                            <tr>
                                <td>{{ $j->guru_utama }}</td>
                                <td>{{ ucfirst($j->hari) }}</td>
                                <td>{{ $j->jam_mulai }} - {{ $j->jam_selesai }}</td>
                                <td>{{ $j->status }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </section>
        @endif
    </main>
</body>

</html>
