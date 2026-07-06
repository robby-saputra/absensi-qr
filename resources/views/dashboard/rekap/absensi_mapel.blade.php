<!DOCTYPE html>
<html lang="id"><head>
@include('layouts.favicon')
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Rekap Absensi Mapel</title><link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-absensi-mapel.css') }}">
</head><body>
@include('layouts.sidebar_admin')
<main id="content" class="content" data-print-title="Rekap Absensi Mapel" data-print-date="{{ now()->format('d-m-Y H:i') }}"><div class="mapel-page">
@include('layouts.alerts')
@php $exportQuery=http_build_query($filters); @endphp
<section class="mapel-hero"><div><span>Laporan Pembelajaran</span><h1>Rekap Absensi Mapel</h1><p>Data kehadiran siswa per sesi JP dari guru utama maupun guru pengganti.</p></div><div class="hero-actions"><a href="/dashboard/admin" class="btn ghost">Kembali</a><a href="/dashboard/admin/absensi-mapel/create" class="btn light">+ Tambah Absensi</a></div></section>
<section class="summary-grid"><article><span>Total Siswa/Sesi</span><strong>{{ $ringkasan['total'] }}</strong><small>baris sesuai jadwal</small></article><article class="green"><span>Hadir</span><strong>{{ $ringkasan['hadir'] }}</strong><small>sesi diikuti siswa</small></article><article class="orange"><span>Terlambat</span><strong>{{ $ringkasan['telat'] }}</strong><small>perlu perhatian</small></article><article class="purple"><span>Izin & Sakit</span><strong>{{ $ringkasan['izin_sakit'] }}</strong><small>ketidakhadiran berizin</small></article><article class="red"><span>Alfa</span><strong>{{ $ringkasan['alfa'] }}</strong><small>tidak mengikuti mapel</small></article><article class="gray"><span>Belum Absen Mapel</span><strong>{{ $ringkasan['belum'] }}</strong><small>belum memiliki record</small></article></section>

<section class="mapel-panel"><header class="panel-head"><div><span>Data Absensi per JP</span><h2>Riwayat Kehadiran Mata Pelajaran</h2><p>{{ $data->count() }} hasil ditemukan</p></div><div class="export-actions"><button type="button" class="tool-btn print" onclick="printReport('Rekap Absensi Mapel')">Print</button><button type="button" class="tool-btn excel" onclick="exportTableToExcel('rekap-absensi-mapel','Rekap Absensi Mapel')">Excel</button><a class="tool-btn pdf" target="_blank" href="/dashboard/admin/rekap/absensi-mapel-pdf?{{ $exportQuery }}">PDF Resmi</a></div></header>
<form method="GET" class="mapel-filter">
<label class="search"><span>Pencarian</span><input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Siswa, NIS, atau guru..."></label>
<label><span>Tanggal</span><input type="date" name="tanggal" value="{{ $filters['tanggal'] }}"></label>
<label><span>Tahun Ajaran</span><select name="tahun_ajaran_id"><option value="">Semua tahun ajaran</option>@foreach($tahunAjaran as $ta)<option value="{{ $ta->id }}" {{ (string)$filters['tahun_ajaran_id']===(string)$ta->id?'selected':'' }}>{{ $ta->nama }} · {{ ucfirst($ta->semester) }}{{ $ta->aktif?' (Aktif)':'' }}</option>@endforeach</select></label>
<label><span>Kelas</span><select name="kelas_id"><option value="">Semua kelas</option>@foreach($kelas as $k)<option value="{{ $k->id }}" {{ (string)$filters['kelas_id']===(string)$k->id?'selected':'' }}>{{ $k->nama_kelas }}</option>@endforeach</select></label>
<label><span>Mata Pelajaran</span><select name="mapel_id"><option value="">Semua mapel</option>@foreach($mapel as $m)<option value="{{ $m->id }}" {{ (string)$filters['mapel_id']===(string)$m->id?'selected':'' }}>{{ $m->nama_mapel }}</option>@endforeach</select></label>
<label><span>Jam Pelajaran</span><select name="jp"><option value="">Semua JP</option>@for($jp=1;$jp<=12;$jp++)<option value="{{ $jp }}" {{ (string)$filters['jp']===(string)$jp?'selected':'' }}>JP {{ $jp }}</option>@endfor</select></label>
<label><span>Status</span><select name="status"><option value="">Semua status</option>@foreach(['belum'=>'Belum Absen Mapel','hadir'=>'Hadir','telat'=>'Terlambat','izin'=>'Izin','sakit'=>'Sakit','alfa'=>'Alfa'] as $value=>$label)<option value="{{ $value }}" {{ $filters['status']===$value?'selected':'' }}>{{ $label }}</option>@endforeach</select></label>
<div class="filter-actions"><button class="btn apply">Terapkan</button><a href="/dashboard/admin/rekap/absensi-mapel" class="btn reset">Reset</a></div></form>

<div class="scroll-notice"><span>Geser tabel ke kanan untuk melihat seluruh data dan tombol aksi.</span><strong>← Geser →</strong></div>
<div class="table-wrap"><table id="rekap-absensi-mapel" class="mapel-table"><thead><tr><th>Siswa</th><th>Absen Harian Piket</th><th>Kelas & Mapel</th><th>Sesi JP</th><th>Guru Pelaksana</th><th>Jam Absen Mapel</th><th>Status Mapel</th><th class="action-head">Aksi</th></tr></thead><tbody>
@forelse($data as $row)
@php
$penggantiBertugas=(($row->status_guru_harian??'normal')!=='normal' && ($row->pengganti_status??null)==='bertugas');
$pelaksana=$penggantiBertugas?($row->guru_pengganti??'-'):$row->guru_utama;
$inisial=collect(explode(' ',trim($row->nama_siswa)))->filter()->take(2)->map(fn($kata)=>strtoupper(substr($kata,0,1)))->implode('');
$jpAkhir=(int)$row->jam_ke_mulai+(int)$row->jumlah_jp-1;
@endphp
<tr><td data-label="Siswa"><div class="student"><span>{{ $inisial?:'?' }}</span><div><strong>{{ $row->nama_siswa }}</strong><small>NIS {{ $row->nis??'-' }}</small></div></div></td>
<td data-label="Absen Harian"><div class="daily-attendance">@if(in_array($row->status_harian_masuk,['izin','sakit','alfa','alpa']))<span class="daily-badge {{ $row->status_harian_masuk }}">{{ ucfirst($row->status_harian_masuk) }}</span>@elseif($row->jam_harian_masuk)<strong>{{ substr($row->jam_harian_masuk,0,5) }}</strong><small>{{ ucfirst($row->status_harian_masuk??'hadir') }}</small>@else<span class="daily-badge alfa">Alfa</span>@endif</div></td>
<td data-label="Kelas & Mapel"><span class="class-badge">{{ $row->nama_kelas??'-' }}</span><strong class="subject">{{ $row->nama_mapel }}</strong></td>
<td data-label="Sesi JP"><span class="jp-badge">{{ $row->jumlah_jp>1?'JP '.$row->jam_ke_mulai.'–'.$jpAkhir:'JP '.$row->jam_ke_mulai }}</span><small>{{ substr($row->jam_mulai,0,5) }}–{{ substr($row->jam_selesai,0,5) }}</small></td>
<td data-label="Guru"><div class="teacher"><strong>{{ $pelaksana }}</strong><small>{{ $penggantiBertugas?'Guru pengganti':'Guru utama' }}{{ $penggantiBertugas?' · menggantikan '.$row->guru_utama:'' }}</small></div></td>
<td data-label="Scan"><strong class="scan-time">{{ $row->jam_scan?substr($row->jam_scan,0,5):'-' }}</strong></td>
<td data-label="Status"><span class="status-badge {{ strtolower($row->status) }}">{{ $row->status==='belum'?'Belum Absen Mapel':ucfirst($row->status) }}</span></td>
<td data-label="Aksi">@if($row->id)<div class="row-actions"><a class="action-btn view" href="/dashboard/admin/absensi-mapel/{{ $row->id }}">Lihat</a><a class="action-btn edit" href="/dashboard/admin/absensi-mapel/edit/{{ $row->id }}">Ubah</a><a class="action-btn delete" href="/dashboard/admin/absensi-mapel/delete/{{ $row->id }}" data-confirm="Hapus absensi mapel {{ $row->nama_siswa }} pada {{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}?">Hapus</a></div>@else<a class="action-btn create" href="/dashboard/admin/absensi-mapel/create?jadwal_id={{ $row->jadwal_id }}&siswa_id={{ $row->siswa_id }}&tanggal={{ $row->tanggal }}&status=alfa">Buat Data</a>@endif</td></tr>
@empty<tr><td colspan="8"><div class="empty-state"><strong>Data absensi mapel tidak ditemukan</strong><span>Coba ubah tanggal atau filter yang digunakan.</span></div></td></tr>@endforelse
</tbody></table></div></section>
</div></main></body></html>
