<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Guru</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-guru.css') }}">

</head>

<body>

@include('layouts.sidebar_guru')

<main id="content" class="content">

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

@if($isGuruPiketHariIni || $isGuruPiketPenggantiHariIni)

<a
class="btn btn-success"
href="/dashboard/piket"
>

{{ $isGuruPiketPenggantiHariIni && ! $isGuruPiketHariIni ? 'Dashboard Guru Piket Pengganti' : 'Dashboard Guru Piket Hari Ini' }}

</a>

@endif


</div>

</div>






@if(in_array($activeGuruPage, ['dashboard','jadwal']))
<table id="jadwal-hari-ini">

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
@endif

@if(in_array($activeGuruPage, ['dashboard','verifikasi']))
<section class="attendance-panel" id="verifikasi-absensi">
    <div class="section-head">
        <div>
            <h3>Verifikasi Absensi Harian Siswa</h3>
            <p>Data diambil dari absensi masuk/pulang guru piket, dipisah per kelas yang Anda ajar.</p>
        </div>
    </div>

    <form method="GET" action="/dashboard/guru" class="filter-box">
        <label>
            Tanggal
            <input type="date" name="tanggal" value="{{ $tanggalFilter }}">
        </label>

        <label>
            Hari
            <select name="hari">
                <option value="">Semua Hari</option>
                @foreach(['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $h)
                    <option value="{{ $h }}" {{ $hariFilter == $h ? 'selected' : '' }}>{{ $h }}</option>
                @endforeach
            </select>
        </label>

        <label>
            Bulan
            <select name="bulan">
                <option value="">Semua Bulan</option>
                @foreach(range(1,12) as $b)
                    <option value="{{ $b }}" {{ (string)$bulanFilter === (string)$b ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create(null, $b, 1)->locale('id')->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>
        </label>

        <label>
            Tahun
            <input type="number" name="tahun" min="2020" max="2100" value="{{ $tahunFilter }}" placeholder="Semua tahun">
        </label>

        <label>
            Kelas
            <select name="kelas_id">
                <option value="">Semua Kelas</option>
                @foreach($kelasAjar as $k)
                    <option value="{{ $k->id }}" {{ (string)$kelasFilter === (string)$k->id ? 'selected' : '' }}>
                        {{ $k->nama_kelas }}
                    </option>
                @endforeach
            </select>
        </label>

        <label>
            Jurusan
            <select name="jurusan_id">
                <option value="">Semua Jurusan</option>
                @foreach($jurusan as $jrs)
                    <option value="{{ $jrs->id }}" {{ (string)$jurusanFilter === (string)$jrs->id ? 'selected' : '' }}>
                        {{ $jrs->nama_jurusan }}
                    </option>
                @endforeach
            </select>
        </label>

        <div class="filter-actions">
            <button type="submit" class="btn">Terapkan</button>
            <a href="/dashboard/guru" class="btn disabled">Reset</a>
        </div>
    </form>

    @forelse($absensiKelasAjar->groupBy('nama_kelas') as $namaKelas => $items)
    <h4 class="class-title">{{ $namaKelas ?? 'Tanpa Kelas' }}</h4>
    <table>
        <tr>
            <th>Nama</th>
            <th>NIS</th>
            <th>Jurusan</th>
            <th>Jam Masuk</th>
            <th>Status Masuk</th>
            <th>Jam Pulang</th>
            <th>Status Pulang</th>
            <th>Aksi</th>
        </tr>

        @foreach($items as $a)
            <tr>
                <td>{{ $a->nama }}</td>
                <td>{{ $a->nis ?? '-' }}</td>
                <td>{{ $a->nama_jurusan ?? '-' }}</td>
                <td>{{ $a->jam_masuk ?? '-' }}</td>
                <td>
                    <span class="status {{ $a->status_masuk ? 'status-normal' : 'status-belum' }}">
                        {{ $a->status_masuk ?? 'belum absen' }}
                    </span>
                </td>
                <td>{{ $a->jam_pulang ?? '-' }}</td>
                <td>
                    <span class="status {{ $a->status_pulang ? 'status-normal' : 'status-belum' }}">
                        {{ $a->status_pulang ?? 'belum pulang' }}
                    </span>
                </td>
                <td>
                    <a class="btn btn-success" href="/dashboard/guru/absensi/{{ $a->id }}/view?tanggal={{ $tanggalFilter }}">View</a>
                    <a class="btn btn-purple" href="/dashboard/guru/absensi/{{ $a->id }}/edit?tanggal={{ $tanggalFilter }}">Edit</a>
                </td>
            </tr>
        @endforeach
    </table>
    @empty
        <div class="empty-state">Tidak ada data siswa untuk filter yang dipilih.</div>
    @endforelse
</section>
@endif

@if(in_array($activeGuruPage, ['dashboard','riwayat']))
<section class="attendance-panel" id="riwayat-absensi">
    <div class="section-head">
        <div>
            <h3>Riwayat Absensi Siswa</h3>
            <p>Riwayat 7 hari terakhir untuk siswa di kelas yang Anda ajar hari ini.</p>
        </div>
    </div>

    @forelse($riwayatAbsensiKelasAjar->groupBy('nama_kelas') as $namaKelas => $items)
    <h4 class="class-title">{{ $namaKelas ?? 'Tanpa Kelas' }}</h4>
    <table>
        <tr>
            <th>Tanggal</th>
            <th>Nama</th>
            <th>Jurusan</th>
            <th>Masuk</th>
            <th>Status Masuk</th>
            <th>Pulang</th>
            <th>Status Pulang</th>
        </tr>

        @foreach($items as $r)
            <tr>
                <td>{{ $r->tanggal }}</td>
                <td>{{ $r->nama }}</td>
                <td>{{ $r->nama_jurusan ?? '-' }}</td>
                <td>{{ $r->jam_masuk ?? '-' }}</td>
                <td>{{ $r->status_masuk ?? '-' }}</td>
                <td>{{ $r->jam_pulang ?? '-' }}</td>
                <td>{{ $r->status_pulang ?? '-' }}</td>
            </tr>
        @endforeach
    </table>
    @empty
        <div class="empty-state">Belum ada riwayat absensi untuk filter yang dipilih.</div>
    @endforelse
</section>
@endif

</main>

</body>
</html>



