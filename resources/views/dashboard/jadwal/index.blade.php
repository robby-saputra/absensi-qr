<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>

Kelola Jadwal

</title>


<style>

body{

font-family:Arial;

background:#f5f6fa;

padding:30px;

}



h2{

color:#273c75;

}



table{

width:100%;

border-collapse:collapse;

background:white;

box-shadow:

0 2px 8px

rgba(

0,

0,

0,

0.08

);

}



th,

td{

border:

1px solid #ddd;

padding:10px;

text-align:left;

}



th{

background:#273c75;

color:white;

}



.btn{

padding:

8px 14px;

background:

#273c75;

color:

white;

border-radius:

5px;

text-decoration:

none;

}



.btn:hover{

opacity:

0.9;

}



.danger{

color:red;

text-decoration:none;

}



.badge{

padding:

4px 8px;

border-radius:

4px;

font-size:

12px;

color:white;

}



.normal{

background:

#27ae60;

}



.ganti{

background:

#e67e22;

}



</style>

</head>



<body>



<h2>

Kelola Jadwal Pelajaran

</h2>




<p>

<a

class="btn"

href="/dashboard/admin"

>

Kembali

</a>




<a

class="btn"

href="/dashboard/admin/jadwal/create"

>

Tambah Jadwal

</a>


</p>








<table>


<tr>

<th>
Kelas
</th>


<th>
Hari
</th>


<th>
Jam
</th>


<th>
Mapel
</th>


<th>
Guru Utama
</th>



<th>
Guru Pengganti
</th>



<th>
Status
</th>



<th>
Keterangan
</th>



<th>
Aksi
</th>


</tr>





@foreach($jadwal as $j)

<tr>



<td>

{{ $j->nama_kelas }}

</td>




<td>

{{ $j->hari }}

</td>





<td>

{{ $j->jam_mulai }}

-

{{ $j->jam_selesai }}

</td>






<td>

{{ $j->nama_mapel }}

</td>







<td>

{{ $j->nama_guru }}

</td>








<td>

{{

$j->nama_guru_pengganti

??

'-'

}}

</td>








<td>


@if(

$j->status_guru

==

'digantikan'

)

<span class="badge ganti">

Digantikan

</span>


@else

<span class="badge normal">

Normal

</span>

@endif


</td>









<td>

{{

$j->keterangan

??

'-'

}}

</td>








<td>


<a

class="danger"

href="/dashboard/admin/jadwal/delete/{{ $j->id }}"

onclick="return confirm('Hapus jadwal?')"

>

Hapus

</a>


</td>




</tr>

@endforeach




</table>



</body>

</html>