<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Kesehatan Data</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/admin-polish.css') }}?v=20260602-polish">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content admin-polish">
        <div class="polish-hero">
            <div>
                <span class="polish-kicker">Data Quality</span>
                <h1>Dashboard Kesehatan Data</h1>
                <p>Temuan data yang perlu dibenahi superadmin, ditulis dengan bahasa operasional.</p>
            </div>
            <a class="polish-btn secondary" href="/dashboard/admin">Kembali</a>
        </div>

        <div class="polish-stats">
            <div
                class="polish-stat {{ collect($data)->sum(fn($s) => $s['items']->count()) > 0 ? 'danger' : 'success' }}">
                <span>Total Temuan</span>
                <strong>{{ collect($data)->sum(fn($s) => $s['items']->count()) }}</strong>
            </div>
            <div class="polish-stat">
                <span>Area Dicek</span>
                <strong>{{ collect($data)->count() }}</strong>
            </div>
            <div class="polish-stat success">
                <span>Area Aman</span>
                <strong>{{ collect($data)->filter(fn($s) => $s['items']->count() === 0)->count() }}</strong>
            </div>
        </div>

        @foreach ($data as $section)
            <section class="polish-panel">
                <div class="polish-panel-head">
                    <div>
                        <h2>{{ $section['judul'] }}</h2>
                        <p><strong>Masalah:</strong> {{ $section['masalah'] }}</p>
                        <p><strong>Saran:</strong> {{ $section['saran'] }}</p>
                    </div>
                    <span class="polish-badge">{{ $section['items']->count() }} temuan</span>
                </div>

                <table class="polish-table">
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Detail</th>
                        <th>Status</th>
                    </tr>
                    @forelse($section['items'] as $item)
                        <tr>
                            <td>#{{ $item['id'] ?? '-' }}</td>
                            <td>{{ $item['utama'] ?? '-' }}</td>
                            <td>{{ $item['detail'] ?? '-' }}</td>
                            <td><span class="polish-badge">{{ $item['status'] ?? '-' }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="polish-empty">Aman, tidak ada data yang perlu dibenahi.</td>
                        </tr>
                    @endforelse
                </table>
            </section>
        @endforeach
    </main>
</body>

</html>
