<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">

<title>
Tambah Jadwal
</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-jadwal-create.css') }}">

</head>


<body>

@include('layouts.sidebar_admin')

<main id="content" class="content">



<div class="box">


<h2>

Tambah Jadwal Pelajaran

</h2>



<div class="info">

<b>Info:</b>

Guru pengganti digunakan jika guru utama
izin, sakit, atau inval.
Guru pengganti akan menerima jadwal
secara otomatis saat guru utama
berhalangan.

</div>

@if(session('error'))

<div class="info error">

{{ session('error') }}

</div>

@endif

@if($errors->any())

<div class="info error">

{{ $errors->first() }}

</div>

@endif





<form

method="POST"

action="/dashboard/admin/jadwal/store"

>

@csrf

<label>
Tahun Ajaran
</label>

<select name="tahun_ajaran_id">
@foreach($tahunAjaran as $ta)
<option value="{{ $ta->id }}" {{ old('tahun_ajaran_id', $tahunAjaranAktif->id ?? '') == $ta->id ? 'selected' : '' }}>
{{ $ta->nama }} - {{ ucfirst($ta->semester) }} {{ $ta->aktif ? '(Aktif)' : '' }}
</option>
@endforeach
</select>





<label>

Kelas

</label>


<select

name="kelas_id"

required

>

@foreach($kelas as $k)

<option value="{{ $k->id }}">

{{ $k->nama_kelas }}

</option>

@endforeach

</select>








<label>

Hari

</label>


<select

name="hari"

required

>

<option>Senin</option>

<option>Selasa</option>

<option>Rabu</option>

<option>Kamis</option>

<option>Jumat</option>

<option>Sabtu</option>

</select>








<label>

Jam Mulai

</label>


<input

type="time"

name="jam_mulai"

required

>








<label>

Jam Selesai

</label>


<input

type="time"

name="jam_selesai"

required

>








<label>

Mata Pelajaran

</label>


<select

name="mapel_id"

required

>

@foreach($mapels as $m)

<option value="{{ $m->id }}">

{{ $m->nama_mapel }}

</option>

@endforeach

</select>









<label>

Guru Utama

</label>


<select

name="guru_id"

required

>

@foreach($guru as $g)

<option value="{{ $g->id }}">

{{ $g->nama }}

</option>

@endforeach

</select>










<!--
===================================
FITUR BARU
Guru Pengganti
===================================
-->



<label>

Guru Pengganti
(Cadangan)

</label>


<select

name="guru_pengganti_id"

>

<option value="">

--

Tidak Ada

--

</option>


@foreach($guru as $g)

<option value="{{ $g->id }}">

{{ $g->nama }}

</option>

@endforeach


</select>








<label>

Keterangan Untuk Guru Pengganti

</label>


<textarea

name="keterangan"

placeholder="Contoh:
Menggantikan jika guru utama sakit atau izin"

>

</textarea>








<button

type="submit"

>

Simpan Jadwal

</button>




<a

href="/dashboard/admin/jadwal"

class="back"

>

Kembali

</a>



</form>



</div>



</main>

</body>

</html>




