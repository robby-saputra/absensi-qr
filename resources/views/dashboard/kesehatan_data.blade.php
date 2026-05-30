<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kesehatan Data</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>
<body>
@include('layouts.sidebar_admin')

<main id="content" class="content">
    <div class="rekap-head">
        <div>
            <h1>Dashboard Kesehatan Data</h1>
            <p>Temuan data yang perlu dibenahi superadmin, ditulis dengan bahasa operasional.</p>
        </div>
        <a class="btn back" href="/dashboard/admin">Kembali</a>
    </div>

    @foreach($data as $section)
        <section style="margin-top:20px">
            <div class="rekap-head" style="margin-bottom:10px">
                <div>
                    <h2>{{ $section['judul'] }} ({{ $section['items']->count() }})</h2>
                    <p><strong>Masalah:</strong> {{ $section['masalah'] }}</p>
                    <p><strong>Saran:</strong> {{ $section['saran'] }}</p>
                </div>
            </div>

            <table>
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
                        <td><span class="status-pill warn">{{ $item['status'] ?? '-' }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-row">Aman, tidak ada data yang perlu dibenahi.</td>
                    </tr>
                @endforelse
            </table>
        </section>
    @endforeach
</main>
</body>
</html>
