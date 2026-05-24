<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">

<title>
Tambah Jadwal
</title>


<style>

body{
font-family:Arial;
background:#f5f6fa;
padding:30px;
}


.box{

width:650px;

background:white;

padding:25px;

border-radius:10px;

box-shadow:
0 2px 8px rgba(
0,
0,
0,
0.08
);

}


h2{

color:#273c75;

margin-bottom:20px;

}



label{

font-weight:bold;

display:block;

margin-bottom:5px;

}



input,
select,
textarea{

width:100%;

padding:10px;

margin-bottom:15px;

border:

1px solid #ccc;

border-radius:6px;

box-sizing:border-box;

}



textarea{

height:90px;

resize:none;

}



button{

background:#273c75;

color:white;

padding:12px 18px;

border:none;

border-radius:6px;

cursor:pointer;

}



button:hover{

background:#192a56;

}



.back{

background:#7f8c8d;

padding:12px 18px;

color:white;

text-decoration:none;

border-radius:6px;

margin-left:10px;

}


.info{

background:#ecf0f1;

padding:12px;

border-left:

4px solid #273c75;

margin-bottom:20px;

border-radius:5px;

}


</style>

</head>


<body>



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





<form

method="POST"

action="/dashboard/admin/jadwal/store"

>

@csrf





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



</body>

</html>