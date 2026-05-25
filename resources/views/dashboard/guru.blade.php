<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Guru</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-guru.css') }}">

</head>

<body>


<div class="top">

<div>

<h2>Dashboard Guru</h2>

<p>{{ $user->nama }}</p>

<p>Jadwal Hari:
{{ $hari }}
</p>

</div>



<div>

<a
class="btn"
href="/logout">

Logout

</a>



@if($isWaliKelas)

<a
class="btn btn-success"
href="/dashboard/wali"
>

Dashboard Wali Kelas

</a>

@endif


</div>

</div>






<table>

<tr>

<th>Kelas</th>

<th>Mapel</th>

<th>Jam</th>

<th>Guru Pengganti</th>

<th>Status</th>

<th>Aksi Guru</th>

<th>Fitur</th>

</tr>





@forelse($jadwal as $j)

<tr>



<td>

{{ $j->nama_kelas }}

</td>




<td>

{{ $j->nama_mapel }}

</td>





<td>

{{ $j->jam_mulai }}

-

{{ $j->jam_selesai }}

</td>






<td>

<b>

Guru Pengganti:

</b>

<br>

{{ $j->guru_pengganti ?? '-' }}


<div class="info">

{{ $j->keterangan ?? '-' }}

</div>


</td>








<td>


@if($j->role_mengajar=='guru_pengganti')

<span class="status status-pengganti">

Guru Pengganti

</span>

<br>

<small>

Menggantikan:

{{ $j->alasan_tidak_hadir }}

</small>




@elseif($j->status_guru===null)

<span class="status status-belum">

Belum Pilih

</span>




@elseif($j->status_guru=='normal')

<span class="status status-normal">

Hadir

</span>




@else

<span class="status status-ganti">

Digantikan

</span>

<br>

<small>

{{ $j->alasan_tidak_hadir }}

</small>


@endif


</td>










<td>


@if($j->role_mengajar=='guru_pengganti')


<button

class="btn disabled"

disabled>

Mode Guru Pengganti

</button>


<div class="info">

Anda menerima jadwal pengganti

</div>





@elseif($j->status_guru===null)



<form

method="POST"

action="/dashboard/guru/status/{{ $j->id }}"

>

@csrf



<button
class="btn hadir"
name="status"
value="normal">

Hadir

</button>



<button
class="btn izin"
name="status"
value="izin">

Izin

</button>



<button
class="btn sakit"
name="status"
value="sakit">

Sakit

</button>



<button
class="btn inval"
name="status"
value="inval">

Inval

</button>


</form>




@else


<button
class="btn disabled"
disabled>

Status Sudah Dipilih

</button>



<div class="info">

@if($j->status_guru=='normal')

Guru hadir


@else

Guru:

<b>

{{ $j->alasan_tidak_hadir }}

</b>

<br>

Digantikan:

<b>

{{ $j->guru_pengganti }}

</b>


@endif

</div>



@endif



</td>









<td>


{{-- GURU PENGGANTI --}}
@if($j->role_mengajar=='guru_pengganti')


<a

class="btn btn-purple"

href="/dashboard/guru/mulai-sesi/{{ $j->id }}"

>

Mulai Sesi Pengganti

</a>



<div class="info">

⚠ Menggantikan guru utama

<br>

Alasan:

<b>

{{ $j->alasan_tidak_hadir }}

</b>

</div>






{{-- BELUM PILIH --}}
@elseif($j->status_guru===null)



<button
class="btn disabled"
disabled>

Pilih Status Dulu

</button>



<div class="info">

Pilih:

Hadir / Izin / Sakit / Inval

</div>






{{-- HADIR --}}
@elseif($j->status_guru=='normal')


<a

class="btn"

href="/dashboard/guru/mulai-sesi/{{ $j->id }}"

>

Mulai Sesi

</a>



<div class="info">

✅ Guru hadir

</div>






{{-- DIGANTIKAN --}}
@else


<button
class="btn disabled"
disabled>

Sesi Dinonaktifkan

</button>



<div class="info">

Guru pengganti:

<b>

{{ $j->guru_pengganti }}

</b>

akan mengajar

</div>


@endif






<a
class="btn"
href="/dashboard/guru/nilai/{{ $j->id }}">

Input Nilai

</a>



<a
class="btn btn-success"
href="/dashboard/guru/nilai-hari-ini/{{ $j->id }}"
>

Nilai Hari Ini

</a>



<a
class="btn btn-orange"
href="/dashboard/guru/semua-nilai/{{ $j->id }}"
>

Semua Nilai

</a>



</td>



</tr>

@empty

<tr>

<td colspan="7">

Tidak ada jadwal hari ini

</td>

</tr>

@endforelse



</table>

</body>
</html>



