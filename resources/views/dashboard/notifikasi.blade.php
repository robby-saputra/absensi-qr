{{-- File ini menampilkan daftar notifikasi agar pengguna dapat melihat informasi terbaru dari sistem. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi Admin</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-notifikasi.css') }}?v=20260602-notif">
</head>

<body>

    @include('layouts.sidebar_admin')

    <main id="content" class="content notif-page">

        <div class="page-head panel-headline">
            <div>
                <span class="eyebrow">Lonceng Admin</span>
                <h1>Notifikasi</h1>
                <p>Pusat notifikasi superadmin: absensi, guru, jadwal, piket,.</p>
            </div>

            <div class="head-actions">
                <a href="/dashboard/admin/notifikasi-setting" class="btn-secondary">Setting Notifikasi</a>
                <a href="/dashboard/admin" class="btn-back">Kembali</a>
            </div>
        </div>

        <section class="notif-summary">
            @foreach ($labelKategori as $key => $label)
                @php($count = $key === 'semua' ? $ringkasan->sum() : $ringkasan[$key] ?? 0)
                <a class="summary-card {{ $kategoriAktif === $key ? 'active' : '' }}"
                    href="/dashboard/admin/notifikasi?kategori={{ $key }}">
                    <span>{{ $label }}</span>
                    <strong>{{ $count }}</strong>
                </a>
            @endforeach
        </section>

        @if ($notifikasi->count() == 0)
            <div class="empty">
                Belum ada notifikasi.
            </div>
        @else
            <div class="notif-list">
                @foreach ($notifikasi as $n)
                    <article class="notif-card {{ $n->severity ?? 'info' }}">
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
                                <small>{{ $n->label_detail ?? 'Detail' }}</small>
                                <strong>{{ $n->detail_info ?? '-' }}</strong>
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

                        @if (!empty($n->action_url))
                            <a class="btn-secondary" href="{{ $n->action_url }}">
                                {{ $n->action_label ?? 'Tindak Lanjut' }}
                            </a>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif

    </main>

</body>

</html>
