<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tunjuk Pengganti Lanjutan</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-guru_piket-index.css') }}">
</head>
<body>
@include('layouts.sidebar_admin')
<main id="content" class="content"><div class="piket-page">
    <section class="piket-hero"><div><span class="eyebrow">Admin Sekolah</span><h1>Tunjuk Pengganti Lanjutan</h1><p>{{ ucfirst($jadwal->hari) }}, {{ substr($jadwal->jam_mulai,0,5) }}–{{ substr($jadwal->jam_selesai,0,5) }} · {{ \Carbon\Carbon::parse($tanggal)->format('d/m/Y') }}</p></div></section>
    @if(session('error'))<div class="alert error">{{ session('error') }}</div>@endif
    <section class="card">
        <h2>Riwayat pengganti</h2>
        @foreach($chain as $item)<p>{{ $item->urutan_penggantian }}. {{ $item->nama }} — {{ ucfirst(str_replace('_',' ',$item->status_penugasan)) }} @if($item->alasan)({{ $item->alasan }})@endif</p>@endforeach
    </section>
    <section class="card">
        @if($calon->isEmpty())
            <div class="alert error">Tidak ada guru aktif yang bebas dari status berhalangan dan bentrok jadwal pada jam ini.</div>
        @else
        <form method="POST" action="/dashboard/admin/guru-piket/{{ $jadwal->id }}/replacement" class="filter-card">
            @csrf
            <input type="hidden" name="tanggal" value="{{ $tanggal }}">
            <div><label>Guru pengganti</label><select name="guru_id" required><option value="">Pilih guru</option>@foreach($calon as $guru)<option value="{{ $guru->id }}" @selected(old('guru_id')==$guru->id)>{{ $guru->nama }}</option>@endforeach</select></div>
            <div><label>Alasan</label><input name="alasan" value="{{ old('alasan') }}" required maxlength="500"></div>
            <div><label>Catatan</label><input name="catatan" value="{{ old('catatan') }}" maxlength="500"></div>
            <button class="btn btn-primary">Simpan Penugasan</button><a class="btn btn-ghost" href="/dashboard/admin/guru-piket?tanggal={{ $tanggal }}">Batal</a>
        </form>
        @endif
    </section>
</div></main></body></html>
