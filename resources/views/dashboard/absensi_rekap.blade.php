<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Absensi</title>

    <style>

        body{
            font-family:Arial;
            background:#f5f6fa;
            padding:30px;
        }

        .container{
            background:white;
            padding:20px;
            border-radius:10px;
            box-shadow:0 2px 8px rgba(0,0,0,.08);
        }

        .top{
            display:flex;
            justify-content:space-between;
            align-items:center;
            flex-wrap:wrap;
            gap:10px;
        }

        .btn{
            padding:10px 15px;
            border:none;
            border-radius:6px;
            cursor:pointer;
            text-decoration:none;
            color:white;
            background:#273c75;
        }

        .btn:hover{
            opacity:.9;
        }

        .export{
            background:#27ae60;
        }

        .filter{
            display:flex;
            gap:10px;
            margin:20px 0;
            flex-wrap:wrap;
            align-items:end;
        }

        input,
        select{
            padding:9px;
            border:1px solid #ccc;
            border-radius:5px;
        }

        table{
            width:100%;
            border-collapse:collapse;
            margin-top:15px;
        }

        th,
        td{
            border:1px solid #ddd;
            padding:10px;
            text-align:left;
        }

        th{
            background:#273c75;
            color:white;
        }

        tr:nth-child(even){
            background:#f9f9f9;
        }

    </style>

</head>

<body>

<div class="container">

    <div class="top">

        <h2>
            Rekap Absensi
        </h2>

        <a
        href="{{ route('dashboard.admin') }}"
        class="btn">

            Kembali

        </a>

    </div>



<form
method="GET"
action="{{ route('rekap.absensi') }}"
class="filter">


<div>

<label>
Mode
</label>

<br>

<select name="mode">

<option
value="tanggal"

{{ ($filters['mode'] ?? '') == 'tanggal'
? 'selected'
: '' }}>

Per Tanggal

</option>



<option
value="bulan"

{{ ($filters['mode'] ?? '') == 'bulan'
? 'selected'
: '' }}>

Per Bulan

</option>

</select>

</div>



<div>

<label>
Tanggal
</label>

<br>

<input
type="date"
name="tanggal"

value="{{ $filters['tanggal'] ?? '' }}">

</div>




<div>

<label>
Bulan
</label>

<br>

<input
type="month"
name="bulan"

value="{{ $filters['bulan'] ?? '' }}">

</div>




<button
type="submit"
class="btn">

Tampilkan

</button>




<a

href="{{ route('export.absensi',[
'mode'=>$filters['mode'] ?? '',
'tanggal'=>$filters['tanggal'] ?? '',
'bulan'=>$filters['bulan'] ?? ''
]) }}"

class="btn export">

Export Excel (.xlsx)

</a>


</form>





<table>

<tr>

<th>Tanggal</th>

<th>Nama</th>

<th>NIS</th>

<th>Kelas</th>

<th>Jam Masuk</th>

<th>Status Masuk</th>

<th>Jam Pulang</th>

<th>Status Pulang</th>

</tr>



@forelse($absensi as $a)

<tr>

<td>

{{ $a->tanggal }}

</td>


<td>

{{ $a->nama }}

</td>


<td>

{{ $a->nis ?? '-' }}

</td>


<td>

{{ $a->nama_kelas ?? '-' }}

</td>


<td>

{{ $a->jam_masuk ?? '-' }}

</td>


<td>

{{ $a->status_masuk ?? '-' }}

</td>


<td>

{{ $a->jam_pulang ?? '-' }}

</td>


<td>

{{ $a->status_pulang ?? '-' }}

</td>

</tr>



@empty


<tr>

<td colspan="8">

Data absensi tidak tersedia

</td>

</tr>


@endforelse


</table>


</div>


</body>
</html>