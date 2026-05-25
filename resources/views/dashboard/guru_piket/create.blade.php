<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>

Tambah Guru Piket

</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-guru_piket-create.css') }}">

</head>



<body>

@include('layouts.sidebar_admin')

<main id="content" class="content">



<div class="container">



<h1>

Tambah Guru Piket

</h1>



<a

href="/dashboard/admin/guru-piket"

class="btn-kembali">

Kembali

</a>





@if(session('error'))

<div class="alert">

{{ session('error') }}

</div>

@endif







<form

action="/dashboard/admin/guru-piket/store"

method="POST">

@csrf







<label>

Guru Piket

</label>


<span class="note">

Minimal pilih 5 guru

</span>




<div class="guru-box">


@foreach($guru as $g)

<label class="guru-item">


<input

type="checkbox"

name="guru_id[]"

value="{{ $g->id }}">


<div>

{{ $g->nama }}

<br>

<small>

{{ $g->username }}

</small>

</div>


</label>


@endforeach


</div>








<label>

Guru Pengganti 1

</label>


<select

name="guru_pengganti_id">


<option value="">

Tidak Ada

</option>


@foreach($guru as $g)

<option

value="{{ $g->id }}">

{{ $g->nama }}

(

{{ $g->username }}

)

</option>

@endforeach


</select>








<label>

Guru Pengganti 2

</label>


<select

name="guru_pengganti2_id">


<option value="">

Tidak Ada

</option>


@foreach($guru as $g)

<option

value="{{ $g->id }}">

{{ $g->nama }}

(

{{ $g->username }}

)

</option>

@endforeach


</select>








<label>

Hari

</label>


<select

name="hari"

required>


<option value="Senin">

Senin

</option>


<option value="Selasa">

Selasa

</option>


<option value="Rabu">

Rabu

</option>


<option value="Kamis">

Kamis

</option>


<option value="Jumat">

Jumat

</option>


<option value="Sabtu">

Sabtu

</option>


</select>









<label>

Jam Mulai

</label>


<input

type="time"

name="jam_mulai"

required>









<label>

Jam Selesai

</label>


<input

type="time"

name="jam_selesai"

required>








<button

type="submit"

class="btn btn-simpan">

Simpan Guru Piket

</button>




</form>



</div>



</main>

</body>

</html>




