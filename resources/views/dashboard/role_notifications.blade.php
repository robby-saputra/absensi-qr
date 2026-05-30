<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Notifikasi Saya</title>
<link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
<style>
.notif-shell{max-width:1180px}
.notif-head{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:20px;box-shadow:0 14px 34px rgba(15,23,42,.07)}
.notif-summary{display:grid;grid-template-columns:repeat(3,minmax(140px,1fr));gap:12px;margin:16px 0}
.notif-stat{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:14px;box-shadow:0 10px 24px rgba(15,23,42,.05)}
.notif-stat span{display:block;font-size:12px;color:#64748b;font-weight:700}.notif-stat strong{font-size:26px;color:#111827}
.notif-list{display:grid;gap:12px}
.notif-card{background:#fff;border:1px solid #e5e7eb;border-left:5px solid #2563eb;border-radius:8px;padding:16px;box-shadow:0 12px 28px rgba(15,23,42,.06)}
.notif-card.unread{border-left-color:#f59e0b;background:#fffdf7}
.notif-top{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:10px}
.notif-card h3{margin:0;color:#111827;font-size:17px}.notif-card p{margin:8px 0 0;color:#475569;line-height:1.5}
.notif-meta{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
.pill{display:inline-flex;align-items:center;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:800;background:#eef2ff;color:#3730a3}
.pill.time{background:#f1f5f9;color:#334155}.pill.read{background:#dcfce7;color:#166534}.pill.unread{background:#fef3c7;color:#92400e}
.empty-notif{background:#fff;border:1px dashed #cbd5e1;border-radius:8px;padding:26px;text-align:center;color:#64748b}
@media(max-width:800px){.notif-summary{grid-template-columns:1fr}.notif-top{flex-direction:column}}
</style>
</head>
<body>
@include(($user->role ?? '') === 'piket' ? 'layouts.sidebar_piket' : 'layouts.sidebar_guru')
<main id="content" class="content notif-shell">
    @php
        $belum = $items->where('status', 'belum_dibaca')->count();
        $dibaca = $items->where('status', 'dibaca')->count();
    @endphp
    <div class="rekap-head notif-head">
        <div><h1>Notifikasi Saya</h1><p>Informasi pribadi sesuai role dan kelas yang terkait dengan Anda.</p></div>
        <a class="btn back" href="{{ ($user->role ?? '') === 'piket' ? '/dashboard/piket' : '/dashboard/guru' }}">Kembali</a>
    </div>
    <section class="notif-summary">
        <div class="notif-stat"><span>Total Notifikasi</span><strong>{{ $items->count() }}</strong></div>
        <div class="notif-stat"><span>Belum Dibaca</span><strong>{{ $belum }}</strong></div>
        <div class="notif-stat"><span>Sudah Dibaca</span><strong>{{ $dibaca }}</strong></div>
    </section>
    <div class="notif-list">
        @forelse($items as $n)
            @php $isUnread = $n->status === 'belum_dibaca'; @endphp
            <article class="notif-card {{ $isUnread ? 'unread' : '' }}">
                <div class="notif-top">
                    <div>
                        <h3>{{ $n->judul }}</h3>
                        <p>{{ $n->pesan }}</p>
                    </div>
                    <span class="pill {{ $isUnread ? 'unread' : 'read' }}">{{ $isUnread ? 'Baru' : 'Dibaca' }}</span>
                </div>
                <div class="notif-meta">
                    <span class="pill time">{{ \Carbon\Carbon::parse($n->created_at)->format('d-m-Y H:i') }}</span>
                    <span class="pill">{{ ucwords(str_replace('_', ' ', $n->kategori ?? 'info')) }}</span>
                </div>
            </article>
        @empty
            <div class="empty-notif">Belum ada notifikasi pribadi.</div>
        @endforelse
    </div>
</main>
</body>
</html>
