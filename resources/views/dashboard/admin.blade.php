<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Admin</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-admin.css') }}">
</head>

<body>

@include('layouts.sidebar_admin')

<main id="content" class="content">

<div class="welcome">
    <h2>Halo, {{ $user->nama }} 👋</h2>
    <p>Selamat datang di dashboard admin</p>
</div>



{{-- Statistik --}}
<div class="cards">

    <div class="card">
        <h3>👨‍🎓 Total Siswa</h3>
        <p>{{ $totalSiswa }}</p>
    </div>

    <div class="card">
        <h3>👩‍🏫 Total Guru</h3>
        <p>{{ $totalGuru }}</p>
    </div>

    <div class="card">
        <h3>🏢 Total Kelas</h3>
        <p>{{ $totalKelas }}</p>
    </div>

    <div class="card">
        <h3>📚 Total Jurusan</h3>
        <p>{{ $totalJurusan }}</p>
    </div>

</div>




{{-- NOTIFIKASI --}}
@php

$notifikasi = DB::table('notifications')
->whereNull('user_id')
->latest('id')
->limit(10)
->get();

@endphp



<div class="notif">

<h2>
🔔 Notifikasi Guru Pengganti
</h2>



@if($notifikasi->count()==0)

<p class="notif-empty">

Belum ada notifikasi

</p>


@else


@foreach($notifikasi as $n)

<div class="notif-item">


<div class="notif-title">

🔴 {{ $n->judul }}

</div>



<div class="notif-detail">

👨‍🏫

{{ $n->pesan }}

</div>




<div class="notif-detail">

📅 Tanggal:

{{ \Carbon\Carbon::parse($n->created_at)->locale('id')->translatedFormat('l, d F Y') }}

</div>




<div class="notif-detail">

🕒 Jam:

{{ \Carbon\Carbon::parse($n->created_at)->format('H:i:s') }}

WIB

</div>





<div class="notif-time">

⏱

{{ \Carbon\Carbon::parse($n->created_at)->diffForHumans() }}

</div>



</div>

@endforeach


@endif



</div>



</main>
</body>
</html>




