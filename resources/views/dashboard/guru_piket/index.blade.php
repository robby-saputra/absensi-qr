<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Guru Piket</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-guru_piket-index.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        <div class="piket-page">
            <section class="piket-hero">
                <div class="hero-copy">
                    <div class="hero-icon"><i class="fa-solid fa-user-shield"></i></div>
                    <div>
                    <span class="eyebrow"><i class="fa-solid fa-circle-check"></i> Manajemen Petugas Sekolah</span>
                    <h1>Data Guru Piket</h1>
                    <p>Kelola jadwal, status harian, dan rantai guru pengganti dalam satu tampilan.</p>
                    <div class="hero-meta">
                        <span><i class="fa-regular fa-calendar"></i> {{ \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('l, d F Y') }}</span>
                        <span><i class="fa-solid fa-users"></i> {{ $ringkasanPiket['guru'] ?? 0 }} guru terjadwal</span>
                    </div>
                    </div>
                </div>
                <div class="hero-actions">
                    <a href="/dashboard/admin" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
                    <a href="/dashboard/admin/guru-piket/create" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Tambah Guru</a>
                    <a href="/dashboard/admin/pdf/guru-piket?tanggal={{ $tanggal }}" target="_blank" class="btn btn-soft"><i class="fa-regular fa-file-pdf"></i> PDF</a>
                </div>
            </section>

            @if(session('success'))
                <div class="alert success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert error">{{ session('error') }}</div>
            @endif

            <section class="summary-grid">
                <div class="summary-card">
                    <span>Total Tim</span>
                    <strong>{{ $ringkasanPiket['tim'] ?? 0 }}</strong>
                </div>
                <div class="summary-card">
                    <span>Total Guru</span>
                    <strong>{{ $ringkasanPiket['guru'] ?? 0 }}</strong>
                </div>
                <div class="summary-card">
                    <span>Sedang Bertugas</span>
                    <strong>{{ $ringkasanPiket['aktif'] ?? 0 }}</strong>
                </div>
            </section>

            <form method="GET" class="filter-card">
                <div class="filter-heading">
                    <span class="filter-icon"><i class="fa-solid fa-filter"></i></span>
                    <div><strong>Filter Jadwal</strong><small>Temukan status piket berdasarkan tanggal dan hari.</small></div>
                </div>
                <div class="filter-fields">
                <div>
                    <label for="tanggal">Tanggal Status</label>
                    <input id="tanggal" type="date" name="tanggal" value="{{ $tanggal }}">
                </div>
                <div>
                    <label for="hari">Filter Hari</label>
                    <select id="hari" name="hari">
                        <option value="">Semua Hari</option>
                        @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $h)
                            <option value="{{ $h }}" {{ ($hari ?? '') == $h ? 'selected' : '' }}>
                                {{ $h }}
                            </option>
                        @endforeach
                    </select>
                </div>
                </div>
                <div class="filter-actions"><a class="btn filter-reset" href="/dashboard/admin/guru-piket">Reset</a><button class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Terapkan</button></div>
            </form>

            @php
                $timPerHari = $timPiket->groupBy('hari');
                $labelHari = [
                    'senin' => 'Senin',
                    'selasa' => 'Selasa',
                    'rabu' => 'Rabu',
                    'kamis' => 'Kamis',
                    'jumat' => 'Jumat',
                    'sabtu' => 'Sabtu',
                    'minggu' => 'Minggu',
                ];
            @endphp

            @forelse($timPerHari as $hariKey => $timHari)
                <section class="day-section">
                    <div class="day-section-header">
                        <div>
                            <span class="day-badge">{{ $labelHari[$hariKey] ?? ucfirst($hariKey) }}</span>
                            <h2>{{ $timHari->sum('jumlah') }} guru piket</h2>
                        </div>
                        <span>{{ $timHari->count() }} tim</span>
                    </div>

                    <div class="team-grid">
                        @foreach($timHari as $tim)
                            <article class="team-card" style="--delay: {{ $loop->index * 70 }}ms">
                                <div class="team-card-header">
                                    <div>
                                        <h2>
                                            {{ $tim->jam_mulai ? substr($tim->jam_mulai, 0, 5) : '-' }}
                                            -
                                            {{ $tim->jam_selesai ? substr($tim->jam_selesai, 0, 5) : '-' }}
                                        </h2>
                                        <p>{{ $tim->jumlah }} anggota guru piket</p>
                                    </div>
                                    <div class="member-actions">
                                        <span class="team-status {{ $tim->status_class }}">{{ $tim->status }}</span>
                                        <a href="/dashboard/admin/guru-piket/delete-team/{{ $tim->anggota->first()->id }}" class="mini-btn hapus" data-confirm="Yakin hapus seluruh tim guru piket ini? Semua anggota utama dan penggantinya akan dihapus dari jadwal ini.">Hapus Tim</a>
                                    </div>
                                </div>

                                <div class="team-meter">
                                    <span style="width: {{ min(100, max(10, $tim->jumlah * 20)) }}%"></span>
                                </div>

                                <div class="member-list">
                                    @foreach($tim->anggota as $g)
                                        @php
                                            $statusClass = match ($g->status_harian ?? null) {
                                                'hadir' => 'sedang',
                                                'izin' => 'izin',
                                                'sakit' => 'sakit',
                                                'digantikan' => 'ganti',
                                                'selesai' => 'selesai',
                                                default => 'akan',
                                            };
                                            $inisial = collect(explode(' ', trim($g->nama)))
                                                ->filter()
                                                ->take(2)
                                                ->map(fn ($kata) => strtoupper(substr($kata, 0, 1)))
                                                ->implode('');
                                        @endphp
                                        <div class="member-card">
                                            <div class="avatar">{{ $inisial ?: 'GP' }}</div>
                                            <div class="member-main">
                                                <strong>{{ $g->nama }}</strong>
                                                <span class="member-status {{ $statusClass }}">{{ $g->status_harian_label }}</span>
                                                <small>Pengganti: {{ $g->nama_pengganti ?? '-' }}</small>
                                                <small>Petugas aktif: {{ $g->active_officer ?? 'Belum tersedia' }}</small>
                                                <small>{{ ucfirst($g->hari) }} | {{ substr($g->jam_mulai, 0, 5) }} - {{ substr($g->jam_selesai, 0, 5) }}</small>
                                                @if(($g->replacement_chain ?? collect())->isNotEmpty())
                                                    <div class="replacement-chain">
                                                        <strong>Rantai penggantian</strong>
                                                        @foreach($g->replacement_chain as $replacement)
                                                            <small>
                                                                {{ $replacement->urutan_penggantian }}. {{ $replacement->nama_pengganti_rantai }}
                                                                — {{ ucfirst(str_replace('_', ' ', $replacement->status_penugasan)) }}
                                                                / {{ ucfirst($replacement->status_kehadiran ?? 'belum konfirmasi') }}
                                                                <br>Ditunjuk {{ optional(\Carbon\Carbon::parse($replacement->created_at))->format('d/m/Y H:i') }}
                                                                oleh {{ $replacement->nama_admin ?? 'Sistem' }}
                                                                @if($replacement->alasan) — {{ $replacement->alasan }} @endif
                                                            </small>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="member-actions">
                                                <a href="/dashboard/admin/guru-piket/edit/{{ $g->id }}" class="mini-btn edit">Edit</a>
                                                @if($g->needs_replacement ?? false)
                                                    <a href="/dashboard/admin/guru-piket/{{ $g->id }}/replacement?tanggal={{ $tanggal }}" class="mini-btn edit">Tunjuk Pengganti Lanjutan</a>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="empty-state">
                    <strong>Belum ada data guru piket</strong>
                    <span>Tambahkan jadwal guru piket untuk mulai mengelompokkan per hari.</span>
                </div>
            @endforelse
        </div>
    </main>
</body>

</html>
