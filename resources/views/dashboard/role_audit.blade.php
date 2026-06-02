<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Riwayat Perubahan</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
    <style>
        .audit-shell {
            max-width: 1180px
        }

        .audit-head {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .07)
        }

        .audit-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(140px, 1fr));
            gap: 12px;
            margin: 16px 0
        }

        .audit-stat {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .05)
        }

        .audit-stat span {
            display: block;
            font-size: 12px;
            color: #64748b;
            font-weight: 800
        }

        .audit-stat strong {
            font-size: 26px;
            color: #111827
        }

        .timeline {
            display: grid;
            gap: 12px;
            position: relative
        }

        .audit-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .06);
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 16px
        }

        .audit-time {
            border-right: 1px solid #e5e7eb;
            padding-right: 14px;
            color: #475569;
            font-weight: 800;
            font-size: 13px
        }

        .audit-card h3 {
            margin: 0 0 8px;
            color: #111827;
            font-size: 17px
        }

        .audit-card p {
            margin: 0;
            color: #475569;
            line-height: 1.5
        }

        .audit-meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 12px
        }

        .pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 800;
            background: #eef2ff;
            color: #3730a3
        }

        .pill.actor {
            background: #f1f5f9;
            color: #334155
        }

        .pill.table {
            background: #ecfeff;
            color: #155e75
        }

        .pill.action {
            background: #fef3c7;
            color: #92400e
        }

        .empty-audit {
            background: #fff;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 34px;
            text-align: center;
            color: #64748b;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .04)
        }

        .empty-audit h3 {
            margin: 0 0 8px;
            color: #111827
        }

        .empty-audit p {
            margin: 0
        }

        @media(max-width:800px) {
            .audit-summary {
                grid-template-columns: 1fr
            }

            .audit-card {
                grid-template-columns: 1fr
            }

            .audit-time {
                border-right: 0;
                border-bottom: 1px solid #e5e7eb;
                padding: 0 0 10px
            }
        }
    </style>
</head>

<body>
    @include(($user->role ?? '') === 'piket' ? 'layouts.sidebar_piket' : 'layouts.sidebar_guru')
    <main id="content" class="content audit-shell">
        @php
            $totalUpdate = $logs->where('aksi', 'update')->count();
            $totalCreate = $logs->where('aksi', 'create')->count();
        @endphp
        <div class="rekap-head audit-head">
            <div>
                <h1>Riwayat Perubahan</h1>
                <p>Catatan perubahan absensi dan data siswa yang masih terkait dengan akses Anda.</p>
            </div>
            <a class="btn back"
                href="{{ ($user->role ?? '') === 'piket' ? '/dashboard/piket' : '/dashboard/guru' }}">Kembali</a>
        </div>
        <section class="audit-summary">
            <div class="audit-stat"><span>Total Riwayat</span><strong>{{ $logs->count() }}</strong></div>
            <div class="audit-stat"><span>Data Dibuat</span><strong>{{ $totalCreate }}</strong></div>
            <div class="audit-stat"><span>Data Diubah</span><strong>{{ $totalUpdate }}</strong></div>
        </section>
        <div class="timeline">
            @forelse($logs as $log)
                <article class="audit-card">
                    <div class="audit-time">
                        {{ \Carbon\Carbon::parse($log->created_at)->format('d-m-Y') }}<br>{{ \Carbon\Carbon::parse($log->created_at)->format('H:i') }}
                    </div>
                    <div>
                        <h3>{{ $log->judul ?? 'Perubahan Data' }}</h3>
                        <p>Perubahan dilakukan oleh {{ $log->user_name ?? 'Sistem' }} pada data
                            {{ $log->tabel ?? '-' }}.</p>
                        <div class="audit-meta">
                            <span class="pill actor">{{ $log->user_name ?? 'Sistem' }} ·
                                {{ $log->user_role ?? '-' }}</span>
                            <span class="pill action">{{ ucwords(str_replace('_', ' ', $log->aksi)) }}</span>
                            <span class="pill table">{{ $log->tabel }} #{{ $log->record_id }}</span>
                        </div>
                    </div>
                </article>
            @empty
                <div class="empty-audit">
                    <h3>Belum Ada Perubahan</h3>
                    <p>Kalau nanti ada absensi atau catatan siswa yang diubah, riwayatnya akan muncul di sini.</p>
                </div>
            @endforelse
        </div>
    </main>
</body>

</html>
