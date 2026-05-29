<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>

Kelola Jadwal

</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-jadwal-index.css') }}">

</head>



<body>

@include('layouts.sidebar_admin')

<main id="content" class="content">



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

<a
class="btn"
href="/dashboard/admin/jadwal/import"
>

Import Excel

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

class="btn edit"

href="/dashboard/admin/jadwal/edit/{{ $j->id }}"

>

Edit

</a>


<a

class="btn hapus"

href="/dashboard/admin/jadwal/delete/{{ $j->id }}"

data-confirm="Hapus jadwal?"

>

Hapus

</a>


</td>




</tr>

@endforeach




</table>




</main>

</body>

</html>




