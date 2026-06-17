<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Preview Arsip</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-arsip.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content arsip-page">
        @include('layouts.alerts')

        <section class="arsip-hero">
            <div>
                <span class="arsip-kicker">Preview Arsip</span>
                <h1>{{ $table }} #{{ $row->id }}</h1>
                <p>Periksa isi data sebelum dipulihkan atau dihapus permanen.</p>
            </div>
            <div class="arsip-hero-actions">
                <a class="btn back" href="/dashboard/admin/arsip?table={{ $table }}">Kembali</a>
                <form method="POST" action="/dashboard/admin/arsip/restore">
                    @csrf
                    <input type="hidden" name="table" value="{{ $table }}">
                    <input type="hidden" name="id" value="{{ $row->id }}">
                    <button class="btn" type="submit">Restore Data Ini</button>
                </form>
            </div>
        </section>

        <section class="arsip-table-card">
            <div class="arsip-toolbar">
                <div>
                    <strong>Isi Data</strong>
                    <span>Field sensitif disembunyikan</span>
                </div>
            </div>
            <div class="arsip-detail-grid">
                @foreach ((array) $row as $key => $value)
                    @continue(in_array($key, ['password', 'remember_token']))
                    <div>
                        <span>{{ str_replace('_', ' ', $key) }}</span>
                        <strong>{{ is_scalar($value) ? ($value ?: '-') : json_encode($value) }}</strong>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="arsip-table-card arsip-audit-card">
            <div class="arsip-toolbar">
                <div>
                    <strong>Audit Penghapusan</strong>
                    <span>Jejak aksi terakhir yang mengarsipkan data</span>
                </div>
            </div>
            <div class="arsip-detail-grid audit">
                <div><span>User</span><strong>{{ $audit->user_name ?? '-' }}</strong></div>
                <div><span>Waktu</span><strong>{{ $audit->created_at ?? '-' }}</strong></div>
                <div><span>IP</span><strong>{{ $audit->ip_address ?? '-' }}</strong></div>
            </div>
        </section>
    </main>
</body>

</html>
