<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<title>Data Guru Piket</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-guru_piket-index.css') }}">

</head>



<body>

@include('layouts.sidebar_admin')

<main id="content" class="content">



<div class="container">


<div class="top">


<h1>

Data Guru Piket

</h1>



<div class="group-btn">


<a
href="/dashboard/admin"
class="btn btn-kembali">

← Kembali

</a>



<a
href="/dashboard/admin/guru-piket/create"
class="btn btn-tambah">

+ Tambah Guru Piket

</a>


</div>


</div>





@if(session('success'))

<div class="success">

{{ session('success') }}

</div>

@endif



@if(session('error'))

<div class="error">

{{ session('error') }}

</div>

@endif






<form method="GET" class="filter">


<select name="hari">

<option value="">

Semua Hari

</option>


@foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $h)

<option

value="{{ $h }}"

{{ ($hari ?? '') == $h ? 'selected' : '' }}

>

{{ $h }}

</option>

@endforeach


</select>



<button class="btn btn-tambah">

Cari

</button>



</form>








<table>


<tr>

<th>No</th>

<th>Guru Piket</th>

<th>Guru Pengganti</th>

<th>Hari</th>

<th>Jam</th>

<th>Status</th>

<th>Aksi</th>

</tr>




@forelse($guruPiket as $g)

<tr>


<td>

{{ $loop->iteration }}

</td>



<td>

{{ $g->nama }}

</td>



<td>

{{ $g->guru_pengganti ?? '-' }}

</td>



<td>

<span class="badge badge-hari">

{{ ucfirst($g->hari) }}

</span>

</td>



<td>

@if($g->jam_mulai && $g->jam_selesai)

<span class="badge badge-jam">

{{ substr($g->jam_mulai,0,5) }}

-

{{ substr($g->jam_selesai,0,5) }}

</span>

@else

-

@endif

</td>





<td>


@if($g->status=='Akan Bertugas')

<span class="status akan">

🔵 Akan Bertugas

</span>


@elseif($g->status=='Sedang Bertugas')

<span class="status sedang">

🔥 Sedang Bertugas

</span>


@elseif($g->status=='Izin')

<span class="status izin">

⚫ Izin

</span>


@elseif($g->status=='Sakit')

<span class="status sakit">

🤒 Sakit

</span>


@elseif($g->status=='Digantikan')

<span class="status ganti">

🔄 Digantikan

</span>


@elseif($g->status=='Selesai')

<span class="status selesai">

🟢 Selesai

</span>

@endif


</td>






<td>

<a

href="/dashboard/admin/guru-piket/edit/{{ $g->id }}"

class="btn edit"

>

Edit

</a>

<a

href="/dashboard/admin/guru-piket/delete/{{ $g->id }}"

class="hapus"

data-confirm="Yakin hapus guru piket?"

>

Hapus

</a>

</td>



</tr>


@empty

<tr>

<td colspan="7" class="kosong">

Belum ada data guru piket

</td>

</tr>

@endforelse




</table>



</div>




</main>

</body>

</html>




