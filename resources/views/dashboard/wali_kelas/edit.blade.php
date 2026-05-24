<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>

Edit Wali Kelas

</title>


<style>

*{

margin:0;
padding:0;
box-sizing:border-box;

}


body{

font-family:

Arial,sans-serif;

background:

#f4f6f9;

padding:

30px;

}


.container{

max-width:

700px;

margin:auto;

background:

white;

padding:

30px;

border-radius:

12px;

box-shadow:

0 2px 10px rgba(
0,
0,
0,
0.1
);

}


h2{

margin-bottom:

25px;

color:

#2c3e50;

}


/* LABEL */

label{

display:block;

margin-bottom:

8px;

font-weight:

bold;

color:

#34495e;

}


/* INPUT */

input,

select{

width:100%;

padding:

12px;

border:

1px solid #ddd;

border-radius:

8px;

margin-bottom:

20px;

font-size:

15px;

}


/* BUTTON */

.btn{

display:inline-block;

padding:

10px 18px;

border:none;

border-radius:

8px;

cursor:pointer;

text-decoration:none;

font-size:

14px;

}


/* KEMBALI */

.btn-kembali{

background:

#7f8c8d;

color:

white;

margin-bottom:

20px;

}


.btn-kembali:hover{

background:

#636e72;

}


/* UPDATE */

.btn-update{

background:

#27ae60;

color:

white;

}


.btn-update:hover{

background:

#229954;

}


/* ALERT */

.alert{

padding:

12px;

background:

#f8d7da;

color:

#721c24;

border-radius:

8px;

margin-bottom:

20px;

}


</style>

</head>



<body>



<div class="container">


<h2>

Edit Wali Kelas

</h2>



<a

href="/dashboard/admin/wali-kelas"

class="btn btn-kembali">

Kembali

</a>



@if(session('error'))

<div class="alert">

{{ session('error') }}

</div>

@endif




<form

method="POST"

action="/dashboard/admin/wali-kelas/update/{{ $kelas->id }}">

@csrf



<label>

Kelas

</label>


<input

type="text"

value="{{ $kelas->nama_kelas }}"

readonly>




<label>

Pilih Wali Kelas

</label>



<select

name="wali_kelas_id"

required>


<option value="">

-- Pilih Guru --

</option>



@foreach($guru as $g)

<option

value="{{ $g->id }}"

{{

$kelas->wali_kelas_id

==

$g->id

?

'selected'

:

''

}}>


{{ $g->nama }}

(

{{ $g->username }}

)


</option>

@endforeach


</select>




<button

type="submit"

class="btn btn-update">

Update Wali Kelas

</button>



</form>


</div>



</body>

</html>