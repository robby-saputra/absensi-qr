<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pengganti Guru Mapel</title><link rel="stylesheet" href="{{ asset('css/pages/dashboard-jadwal-create.css') }}"></head>
<body>@include('layouts.sidebar_admin')
<main id="content" class="content"><div class="form-page"><section class="form-hero"><div><span class="eyebrow">Penugasan Guru</span><h1>Tugaskan Pengganti Lanjutan</h1><p>{{ $jadwal->nama_mapel }} · {{ $jadwal->nama_kelas }} · {{ ucfirst($jadwal->hari) }} {{ substr($jadwal->jam_mulai,0,5) }}–{{ substr($jadwal->jam_selesai,0,5) }}</p></div></section>
@if(session('error'))<div class="alert error">{{ session('error') }}</div>@endif
<section class="form-card"><p>Guru utama: <strong>{{ $jadwal->nama_guru }}</strong> ({{ ucfirst($state->primary_status) }})</p>
@if($state->chain->isNotEmpty())<p>Riwayat: @foreach($state->chain as $r)<strong>#{{ $r->urutan_penggantian }} {{ $r->nama_pengganti }}</strong> — {{ ucfirst(str_replace('_',' ',$r->status_penugasan)) }}@if(!$loop->last), @endif @endforeach</p>@endif
<form method="POST" action="/dashboard/admin/jadwal/{{ $jadwal->id }}/replacement">@csrf<input type="hidden" name="tanggal" value="{{ $tanggal }}">
<label>Guru Pengganti<select name="guru_id" required><option value="">Pilih guru tersedia</option>@foreach($calon as $guru)<option value="{{ $guru->id }}" {{ old('guru_id')==$guru->id?'selected':'' }}>{{ $guru->nama }}</option>@endforeach</select></label>
<label>Alasan<textarea name="alasan" required maxlength="500">{{ old('alasan') }}</textarea></label><div class="form-actions"><a class="btn" href="/dashboard/admin/jadwal">Batal</a><button class="btn" type="submit">Simpan Penugasan</button></div></form></section></div></main></body></html>
