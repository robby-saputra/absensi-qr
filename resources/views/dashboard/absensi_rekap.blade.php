<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Absensi</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-absensi_rekap.css') }}">

</head>

<body>

@include('layouts.sidebar_admin')

<main id="content" class="content">

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


</main>

</body>
</html>




