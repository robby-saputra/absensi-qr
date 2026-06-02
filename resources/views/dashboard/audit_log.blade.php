<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Audit Log</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/admin-polish.css') }}?v=20260602-polish">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content admin-polish">
        <div class="polish-hero">
            <div>
                <span class="polish-kicker">Jejak Sistem</span>
                <h1>Audit Log</h1>
                <p>Catatan perubahan data penting untuk keamanan dan bukti.</p>
            </div>
            <div class="polish-actions">
                <a href="/dashboard/admin" class="polish-btn secondary">Kembali</a>
                <a href="/dashboard/admin/pdf/audit-log" target="_blank" class="polish-btn">PDF Resmi</a>
            </div>
        </div>

        <form method="GET" class="polish-filter">
            <label>
                Cari
                <input type="text" name="q" value="{{ $filters['q'] }}"
                    placeholder="Cari user, aksi, judul, tabel">
            </label>
            <label>
                Aksi
                <select name="aksi">
                    <option value="">Semua Aksi</option>
                    @foreach ($aksiList as $aksi)
                        <option value="{{ $aksi }}" {{ $filters['aksi'] === $aksi ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $aksi)) }}</option>
                    @endforeach
                </select>
            </label>
            <button class="polish-btn" type="submit">Tampilkan</button>
        </form>

        <section class="polish-panel">
            <div class="polish-panel-head">
                <div>
                    <h2>Riwayat Perubahan</h2>
                    <p>{{ $logs->total() }} catatan audit ditemukan.</p>
                </div><span class="polish-badge">{{ $logs->count() }} tampil</span>
            </div>
            <div class="polish-timeline">
                @forelse($logs as $log)
                    <article class="timeline-item">
                        <div class="timeline-time">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i:s') }}
                        </div>
                        <div class="timeline-body">
                            <strong>{{ $log->judul ?? '-' }}</strong>
                            <span>{{ $log->user_name ?? '-' }} ({{ $log->user_role ?? '-' }}) mengubah
                                {{ $log->tabel }} #{{ $log->record_id }}</span>
                        </div>
                        <div class="timeline-meta">
                            <span class="chip">{{ $log->aksi }}</span>
                            <span class="chip">{{ $log->ip_address ?? '-' }}</span>
                            <a class="polish-btn light"
                                href="/dashboard/admin/audit-log/{{ $log->id }}">Detail</a>
                        </div>
                    </article>
                @empty
                    <div class="polish-empty">Belum ada audit log.</div>
                @endforelse
            </div>
        </section>
    </main>
</body>

</html>
