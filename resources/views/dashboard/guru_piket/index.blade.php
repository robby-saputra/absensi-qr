<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<title>Data Guru Piket</title>

<style>

body{
    font-family:Arial,sans-serif;
    background:#f4f6f9;
    padding:30px;
}

.container{
    background:white;
    padding:25px;
    border-radius:14px;
    box-shadow:0 4px 15px rgba(0,0,0,.08);
}

.top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
    flex-wrap:wrap;
    gap:10px;
}

h1{
    margin:0;
    color:#2c3e50;
}


/* BUTTON */

.group-btn{
    display:flex;
    gap:10px;
}

.btn{

    padding:10px 16px;

    border:none;

    border-radius:8px;

    color:white;

    text-decoration:none;

    cursor:pointer;

}

.btn-kembali{

    background:#7f8c8d;

}

.btn-kembali:hover{

    background:#636e72;

}

.btn-tambah{

    background:#273c75;

}

.btn-tambah:hover{

    background:#192a56;

}



/* ALERT */

.success{

    background:#d4edda;

    color:#155724;

    padding:12px;

    border-radius:8px;

    margin-bottom:20px;

}

.error{

    background:#f8d7da;

    color:#721c24;

    padding:12px;

    border-radius:8px;

    margin-bottom:20px;

}



/* FILTER */

.filter{

    display:flex;

    gap:10px;

    margin-bottom:20px;

}

.filter select{

    padding:10px;

    border-radius:8px;

    border:1px solid #ddd;

}



/* TABLE */

table{

    width:100%;

    border-collapse:collapse;

    text-align:center;

}

th{

    background:#273c75;

    color:white;

    padding:15px;

}

td{

    padding:15px;

    border-bottom:1px solid #eee;

}

tr:hover{

    background:#fafafa;

}



/* BADGE */

.badge{

    padding:6px 12px;

    border-radius:20px;

    color:white;

    font-size:13px;

}

.badge-hari{

    background:#3498db;

}

.badge-jam{

    background:#27ae60;

}



/* STATUS */

.status{

    padding:8px 14px;

    border-radius:20px;

    color:white;

    font-size:13px;

    font-weight:bold;

}

.akan{
background:#3498db;
}

.sedang{
background:#e74c3c;
}

.izin{
background:#7f8c8d;
}

.sakit{
background:#8e44ad;
}

.ganti{
background:#f39c12;
}

.selesai{
background:#27ae60;
}



/* HAPUS */

.hapus{

background:#e74c3c;

padding:8px 14px;

border-radius:8px;

text-decoration:none;

color:white;

}

.hapus:hover{

background:#c0392b;

}


.kosong{

padding:20px;

color:#777;

}

</style>

</head>



<body>



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

href="/dashboard/admin/guru-piket/delete/{{ $g->id }}"

class="hapus"

onclick="return confirm('Yakin hapus guru piket?')"

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



</body>

</html>