<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Notifikasi Admin</title>
<link rel="stylesheet" href="{{ asset('css/pages/dashboard-notifikasi.css') }}">
</head>

<body>

@include('layouts.sidebar_admin')

<main id="content" class="content">

<div class="page-head">
    <div>
        <h1>Notifikasi</h1>
        <p>Data guru tidak hadir, guru pengganti, dan pengalihan jadwal.</p>
    </div>

    <a href="/dashboard/admin" class="btn-back">Kembali</a>
</div>

@if($notifikasi->count() == 0)
    <div class="empty">
        Belum ada notifikasi.
    </div>
@else
    <div class="notif-list">
        @foreach($notifikasi as $n)
            <article class="notif-card">
                <div class="notif-top">
                    <span class="type">{{ $n->tipe }}</span>
                    <span class="time">
                        {{ \Carbon\Carbon::parse($n->created_at)->locale('id')->translatedFormat('d F Y, H:i') }}
                    </span>
                </div>

                <h2>{{ $n->judul }}</h2>

                <div class="notif-grid">
                    <div>
                        <small>{{ $n->label_utama ?? 'Guru utama' }}</small>
                        <strong>{{ $n->utama }}</strong>
                    </div>

                    <div>
                        <small>{{ $n->label_pengganti ?? 'Guru pengganti' }}</small>
                        <strong>{{ $n->pengganti }}</strong>
                    </div>

                    <div>
                        <small>{{ $n->label_alasan ?? 'Alasan' }}</small>
                        <strong>{{ ucfirst($n->alasan) }}</strong>
                    </div>

                    <div>
                        <small>Waktu</small>
                        <strong>{{ $n->waktu }}</strong>
                    </div>
                </div>

                <p class="detail">{{ $n->detail }}</p>
            </article>
        @endforeach
    </div>
@endif

</main>

</body>
</html>
