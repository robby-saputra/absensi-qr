<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>

Tambah Guru Piket

</title>


<style>

body{

font-family:

Arial,sans-serif;

background:

#f5f6fa;

padding:

30px;

}



.container{

max-width:

800px;

margin:

auto;

background:

white;

padding:

30px;

border-radius:

16px;

box-shadow:

0 5px 20px rgba(
0,
0,
0,
0.08
);

}



h1{

margin-top:

0;

margin-bottom:

25px;

color:

#2c3e50;

}



label{

display:

block;

margin-bottom:

8px;

font-weight:

bold;

color:

#34495e;

}



select,

input{

width:

100%;

padding:

12px;

border:

1px solid #ddd;

border-radius:

10px;

margin-bottom:

20px;

font-size:

15px;

}



.btn{

padding:

12px 20px;

border:

none;

border-radius:

10px;

cursor:

pointer;

font-size:

15px;

}



.btn-simpan{

background:

#27ae60;

color:

white;

}


.btn-simpan:hover{

background:

#229954;

}



.btn-kembali{

background:

#95a5a6;

color:

white;

display:

inline-block;

margin-bottom:

20px;

padding:

10px 18px;

border-radius:

10px;

text-decoration:

none;

}


.btn-kembali:hover{

background:

#7f8c8d;

}



.alert{

background:

#f8d7da;

padding:

12px;

border-radius:

10px;

color:

#721c24;

margin-bottom:

20px;

}



/* BOX GURU */

.guru-box{

border:

1px solid #eee;

background:

#fafafa;

border-radius:

12px;

padding:

15px;

max-height:

250px;

overflow-y:

auto;

margin-bottom:

25px;

}



/* ITEM */

.guru-item{

display:

flex;

align-items:

center;

gap:

12px;

padding:

12px;

border-radius:

10px;

margin-bottom:

8px;

transition:

0.2s;

cursor:

pointer;

}


.guru-item:hover{

background:

#eef4ff;

}



.guru-item input{

width:

18px;

height:

18px;

}



.guru-item small{

color:

#888;

}



/* SUBTITLE */

.note{

color:

#e74c3c;

font-size:

13px;

margin-bottom:

10px;

display:

block;

}

</style>

</head>



<body>



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



</body>

</html>