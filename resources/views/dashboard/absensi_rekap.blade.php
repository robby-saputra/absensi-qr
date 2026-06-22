<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Absensi Harian</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-absensi_rekap.css') }}">
</head>
<body>
@include('layouts.sidebar_admin')
<main id="content" class="content" data-print-title="Rekap Absensi Harian" data-print-date="{{ now()->format('d-m-Y H:i') }}">
<div class="report-page">
    @include('layouts.alerts')
    @php
        $queryExport = http_build_query(collect($filters)->only(['mode','tanggal','bulan','tahun_ajaran_id','kelas_id','status','search'])->all());
        $periode = ($filters['mode'] ?? 'tanggal') === 'bulan'
            ? \Carbon\Carbon::createFromFormat('Y-m', $filters['bulan'])->locale('id')->translatedFormat('F Y')
            : \Carbon\Carbon::parse($filters['tanggal'])->locale('id')->translatedFormat('d F Y');
    @endphp
    <section class="report-hero">
        <div><span>Laporan Kehadiran Sekolah</span><h1>Rekap Absensi Harian</h1><p>Pantau absensi masuk dan pulang siswa untuk periode {{ $periode }}.</p></div>
        <div class="hero-actions"><a href="{{ route('dashboard.admin') }}" class="btn ghost">Kembali</a><a href="/dashboard/admin/absensi/create" class="btn light">+ Tambah Absensi</a></div>
    </section>

    <section class="summary-grid">
        <article class="blue"><span>Total Siswa</span><strong>{{ $ringkasan['total'] }}</strong><small>{{ $ringkasan['tercatat'] }} sudah memiliki record</small></article>
        <article class="green"><span>Hadir Tepat Waktu</span><strong>{{ $ringkasan['hadir'] }}</strong><small>tidak termasuk terlambat</small></article>
        <article class="orange"><span>Terlambat</span><strong>{{ $ringkasan['telat'] }}</strong><small>perlu pemantauan</small></article>
        <article class="purple"><span>Izin</span><strong>{{ $ringkasan['izin'] }}</strong><small>izin tercatat guru piket</small></article>
        <article class="pink"><span>Sakit</span><strong>{{ $ringkasan['sakit'] }}</strong><small>sakit tercatat guru piket</small></article>
        <article class="red"><span>Alfa</span><strong>{{ $ringkasan['alfa'] }}</strong><small>tidak memiliki kehadiran</small></article>
    </section>

    @if (!empty($libur))<div class="holiday-note"><strong>Kalender libur: {{ $libur->judul }}</strong><span>Siswa yang tidak absen pada tanggal ini tidak dihitung alfa.</span></div>@endif

    <section class="report-panel">
        <header class="panel-head"><div><span>Filter Laporan</span><h2>Data Absensi Siswa</h2><p>Sumber: absensi harian masuk dan pulang yang dikelola guru piket.</p></div>
            <div class="export-actions"><button type="button" class="tool-btn print" onclick="printReport('Rekap Absensi Harian')">Print</button><a class="tool-btn excel" href="{{ route('export.absensi') }}?{{ $queryExport }}">Excel</a><a class="tool-btn pdf" target="_blank" href="/dashboard/admin/rekap/absensi-pdf?{{ $queryExport }}">PDF Resmi</a></div>
        </header>

        <form method="GET" action="{{ route('rekap.absensi') }}" class="report-filter">
            <label class="search"><span>Cari Siswa</span><input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama atau NIS siswa..."></label>
            <label><span>Tahun Ajaran</span><select name="tahun_ajaran_id"><option value="">Semua tahun ajaran</option>@foreach($tahunAjaran as $ta)<option value="{{ $ta->id }}" {{ (string)($filters['tahun_ajaran_id'] ?? '') === (string)$ta->id ? 'selected' : '' }}>{{ $ta->nama }} · {{ ucfirst($ta->semester) }}{{ $ta->aktif ? ' (Aktif)' : '' }}</option>@endforeach</select></label>
            <label><span>Kelas</span><select name="kelas_id"><option value="">Semua kelas</option>@foreach($kelas as $k)<option value="{{ $k->id }}" {{ (string)($filters['kelas_id'] ?? '') === (string)$k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>@endforeach</select></label>
            <label><span>Status</span><select name="status"><option value="">Semua status</option>@foreach(['hadir'=>'Hadir','telat'=>'Terlambat','izin'=>'Izin','sakit'=>'Sakit','alfa'=>'Alfa'] as $value=>$label)<option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></label>
            <label><span>Mode Periode</span><select name="mode" id="mode-filter"><option value="tanggal" {{ ($filters['mode'] ?? '') === 'tanggal' ? 'selected' : '' }}>Per tanggal</option><option value="bulan" {{ ($filters['mode'] ?? '') === 'bulan' ? 'selected' : '' }}>Per bulan</option></select></label>
            <label class="date-filter"><span>Tanggal</span><input type="date" name="tanggal" value="{{ $filters['tanggal'] ?? '' }}"></label>
            <label class="month-filter"><span>Bulan</span><input type="month" name="bulan" value="{{ $filters['bulan'] ?? '' }}"></label>
            <div class="filter-actions"><button type="submit" class="btn apply">Terapkan</button><a href="{{ route('rekap.absensi') }}" class="btn reset">Reset</a></div>
        </form>

        <div class="result-meta"><span>Menampilkan <strong>{{ $absensi->count() }}</strong> catatan</span><span>Periode: <strong>{{ $periode }}</strong></span></div>
        <div class="table-wrap"><table class="report-table"><thead><tr><th>Siswa</th><th>Tanggal</th><th>Kelas</th><th>Absen Masuk</th><th>Absen Pulang</th><th>Status</th><th class="action-head">Aksi</th></tr></thead><tbody>
        @forelse($absensi as $a)
            @php
                $statusKhusus = in_array($a->status_masuk, ['izin','sakit']) ? $a->status_masuk : (in_array($a->status_pulang, ['izin','sakit']) ? $a->status_pulang : null);
                $statusMasuk = $statusKhusus ?? $a->status_masuk;
                $punyaAbsenPulang = !empty($a->jam_pulang) || in_array($a->status_pulang, ['pulang', 'pulang_cepat', 'izin', 'sakit', 'alfa', 'alpa']);
                $statusPulang = $punyaAbsenPulang ? ($a->status_pulang === 'hadir' ? 'pulang' : $a->status_pulang) : null;
                $statusSiswa = !empty($libur)
                    ? 'libur'
                    : ($statusKhusus
                        ?? (in_array($a->status_masuk, ['alfa', 'alpa'])
                            ? 'alfa'
                            : (in_array($a->status_masuk, ['telat', 'terlambat'])
                                ? 'telat'
                                : ($a->status_masuk === 'hadir'
                                    ? 'hadir'
                                    : ($filters['status_default_alfa'] ?? 'alfa')))));
                $inisial = collect(explode(' ',trim($a->nama)))->filter()->take(2)->map(fn($kata)=>strtoupper(substr($kata,0,1)))->implode('');
            @endphp
            <tr>
                <td data-label="Siswa"><div class="student"><span>{{ $inisial ?: '?' }}</span><div><strong>{{ $a->nama }}</strong><small>NIS {{ $a->nis ?? '-' }}</small></div></div></td>
                <td data-label="Tanggal"><strong>{{ \Carbon\Carbon::parse($a->tanggal)->format('d/m/Y') }}</strong><small>{{ \Carbon\Carbon::parse($a->tanggal)->locale('id')->translatedFormat('l') }}</small></td>
                <td data-label="Kelas"><span class="class-badge">{{ $a->nama_kelas ?? '-' }}</span></td>
                <td data-label="Masuk"><div class="attendance-time"><strong>{{ $a->jam_masuk ? substr($a->jam_masuk,0,5) : '-' }}</strong><small>{{ ucfirst($statusMasuk ?? 'belum tercatat') }}</small></div></td>
                <td data-label="Pulang"><div class="attendance-time"><strong>{{ $punyaAbsenPulang && $a->jam_pulang ? substr($a->jam_pulang,0,5) : '-' }}</strong>@if($statusPulang)<small>{{ ucfirst(str_replace('_', ' ', $statusPulang)) }}</small>@endif</div></td>
                <td data-label="Status"><span class="status-badge {{ $statusSiswa }}">{{ ucfirst($statusSiswa) }}</span></td>
                <td data-label="Aksi">@if(empty($a->baris_virtual))<div class="row-actions"><a href="/dashboard/admin/absensi/{{ $a->id }}" class="action-btn view">Lihat</a><a href="/dashboard/admin/absensi/edit/{{ $a->id }}" class="action-btn edit">Ubah</a><a href="/dashboard/admin/absensi/delete/{{ $a->id }}" class="action-btn delete" data-confirm="Hapus catatan absensi {{ $a->nama }} pada {{ \Carbon\Carbon::parse($a->tanggal)->format('d/m/Y') }}?">Hapus</a></div>@else<div class="virtual-action"><span class="virtual-note">Belum ada record</span><a class="action-btn create" href="/dashboard/admin/absensi/create?id_siswa={{ $a->id_siswa }}&tanggal={{ $a->tanggal }}&status_masuk={{ $filters['status_default_alfa'] ?? 'alfa' }}">Buat Data</a></div>@endif</td>
            </tr>
        @empty<tr><td colspan="7"><div class="empty-state"><strong>Data absensi tidak ditemukan</strong><span>Coba ubah periode atau filter yang digunakan.</span></div></td></tr>@endforelse
        </tbody></table></div>
    </section>
</div>
</main>
<script>document.addEventListener('DOMContentLoaded',()=>{const mode=document.getElementById('mode-filter'),date=document.querySelector('.date-filter'),month=document.querySelector('.month-filter');const sync=()=>{const monthly=mode.value==='bulan';date.hidden=monthly;month.hidden=!monthly};mode.addEventListener('change',sync);sync()})</script>
</body></html>
