{{-- Dashboard utama admin untuk memantau operasional absensi sekolah secara ringkas. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-admin.css') }}">
</head>

<body class="admin-dashboard-page">
    @include('layouts.sidebar_admin')

    <main id="content" class="content admin-dashboard">
        <section class="admin-hero">
            <div class="admin-hero-copy">
                <span class="admin-kicker"><i class="fa-solid fa-gauge-high"></i> Dashboard Admin</span>
                <h1>Halo, {{ $user->nama ?? 'Admin' }}</h1>
                <p>Pusat kontrol untuk memantau absensi siswa, verifikasi guru, QR aktif, dan item operasional yang perlu ditindaklanjuti.</p>
                <div class="admin-hero-meta">
                    <span><i class="fa-solid fa-calendar-days"></i> {{ now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y') }}</span>
                    <span><i class="fa-regular fa-clock"></i> <strong data-admin-header-time>{{ now('Asia/Jakarta')->format('H:i') }}</strong> WIB</span>
                </div>
            </div>

            <div class="admin-hero-side">
                <div class="dashboard-admin-bell">
                    <button type="button" class="dashboard-admin-bell-btn" data-admin-bell aria-label="Notifikasi admin">
                        <i class="fa-regular fa-bell"></i>
                        <span>Notifikasi</span>
                        @if (($adminBellUnread ?? 0) > 0)
                            <b data-admin-bell-badge>{{ $adminBellUnread > 99 ? '99+' : $adminBellUnread }}</b>
                        @endif
                    </button>
                    <div class="dashboard-admin-bell-dropdown" data-admin-bell-dropdown>
                        <strong>Notifikasi Terbaru</strong>
                        @forelse (($adminBellItems ?? collect()) as $item)
                            <a href="{{ $item->action_url ?? '/dashboard/admin/notifikasi' }}">
                                <span>{{ $item->judul ?? 'Notifikasi' }}</span>
                                <small>{{ $item->pesan ?? '-' }}</small>
                            </a>
                        @empty
                            <p>Belum ada notifikasi penting.</p>
                        @endforelse
                    </div>
                </div>

                <div class="period-card">
                    <span>Periode Aktif</span>
                    <strong>{{ $tahunAjaranAktif ? $tahunAjaranAktif->nama . ' - ' . ucfirst($tahunAjaranAktif->semester) : 'Belum diatur' }}</strong>
                    <a href="/dashboard/admin/pengaturan">Ubah Periode</a>
                </div>
            </div>
        </section>

        @include('layouts.libur_banner')

        @if (($kalenderHariIni ?? collect())->isNotEmpty())
            <section class="calendar-today-banner">
                <strong><i class="fa-solid fa-calendar-check"></i> Kalender Hari Ini</strong>
                @foreach ($kalenderHariIni as $event)
                    <span class="event-chip {{ $event->jenis }}">{{ ucfirst($event->jenis) }}: {{ $event->judul }}</span>
                @endforeach
            </section>
        @endif

        <section class="summary-grid" aria-label="Ringkasan utama">
            @foreach ($summaryCards as $card)
                <article class="summary-card tone-{{ $card['tone'] }}">
                    <div class="summary-icon"><i class="fa-solid {{ $card['icon'] }}"></i></div>
                    <div>
                        <span>{{ $card['label'] }}</span>
                        <strong>{{ number_format($card['value'], 0, ',', '.') }}</strong>
                        <small>{{ $card['meta'] }}</small>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="dashboard-grid">
            <article class="dashboard-panel attendance-panel">
                <header class="panel-title">
                    <div>
                        <span>Operasional Hari Ini</span>
                        <h2>Absensi Hari Ini</h2>
                    </div>
                    <a href="/dashboard/admin/absensi/rekap">Lihat Rekap</a>
                </header>

                <div class="attendance-bars">
                    @foreach ($attendanceOverview as $item)
                        <div class="attendance-row tone-{{ $item['tone'] }}">
                            <div class="attendance-label">
                                <i class="fa-solid {{ $item['icon'] }}"></i>
                                <span>{{ $item['label'] }}</span>
                            </div>
                            <div class="attendance-track" aria-label="{{ $item['label'] }} {{ $item['percent'] }} persen">
                                <span style="width: {{ min($item['percent'], 100) }}%"></span>
                            </div>
                            <strong>{{ number_format($item['value'], 0, ',', '.') }}</strong>
                        </div>
                    @endforeach
                </div>

                <div class="attendance-mini-grid">
                    <div>
                        <span>Izin</span>
                        <strong>{{ $totalIzinHariIni }}</strong>
                    </div>
                    <div>
                        <span>Sakit</span>
                        <strong>{{ $totalSakitHariIni }}</strong>
                    </div>
                    <div>
                        <span>Guru Piket Aktif</span>
                        <strong>{{ $guruPiketAktif }}</strong>
                    </div>
                    <div>
                        <span>QR Aktif</span>
                        <strong>{{ $qrAktifHariIni }}</strong>
                    </div>
                </div>
            </article>

            <article class="dashboard-panel attention-panel">
                <header class="panel-title">
                    <div>
                        <span>Kontrol Admin</span>
                        <h2>Perlu Perhatian</h2>
                    </div>
                </header>

                <div class="attention-list">
                    @foreach ($attentionItems as $item)
                        <a class="attention-item tone-{{ $item->tone }}" href="{{ $item->url }}">
                            <i class="fa-solid {{ $item->icon }}"></i>
                            <span>
                                <strong>{{ $item->label }}</strong>
                                <small>{{ $item->description }}</small>
                            </span>
                            <b>{{ number_format($item->value, 0, ',', '.') }}</b>
                        </a>
                    @endforeach
                </div>
            </article>
        </section>

        <section class="dashboard-grid lower-grid">
            <article class="dashboard-panel quick-panel">
                <header class="panel-title">
                    <div>
                        <span>Navigasi Cepat</span>
                        <h2>Aksi Cepat</h2>
                    </div>
                </header>

                <div class="quick-grid">
                    @foreach ($quickActions as $action)
                        <a href="{{ $action['url'] }}" class="quick-action">
                            <i class="fa-solid {{ $action['icon'] }}"></i>
                            <span>
                                <strong>{{ $action['label'] }}</strong>
                                <small>{{ $action['hint'] }}</small>
                            </span>
                        </a>
                    @endforeach
                </div>
            </article>

            <article class="dashboard-panel distribution-panel">
                <header class="panel-title">
                    <div>
                        <span>Distribusi Data</span>
                        <h2>Jumlah Siswa per Kelas</h2>
                    </div>
                    <a href="/dashboard/admin/kelas">Kelola Kelas</a>
                </header>

                <div class="class-distribution">
                    @forelse ($kelasDistribusi as $kelas)
                        <div class="class-row">
                            <div>
                                <strong>{{ $kelas->nama_kelas }}</strong>
                                <span>{{ $kelas->total }} siswa aktif</span>
                            </div>
                            <div class="class-bar"><span style="width: {{ min($kelas->persen, 100) }}%"></span></div>
                        </div>
                    @empty
                        <div class="empty-state">Belum ada data kelas untuk ditampilkan.</div>
                    @endforelse
                </div>
            </article>
        </section>
    </main>

    <script>
        const headerTime = document.querySelector('[data-admin-header-time]');
        const updateHeaderTime = () => {
            if (!headerTime) return;
            headerTime.textContent = new Intl.DateTimeFormat('id-ID', {
                timeZone: 'Asia/Jakarta',
                hour: '2-digit',
                minute: '2-digit',
                hourCycle: 'h23'
            }).format(new Date());
        };
        updateHeaderTime();
        window.setInterval(updateHeaderTime, 30000);

        document.addEventListener('click', function(event) {
            const button = event.target.closest('[data-admin-bell]');
            const dropdown = document.querySelector('[data-admin-bell-dropdown]');

            if (!dropdown) return;

            if (button) {
                dropdown.classList.toggle('is-open');
                const badge = document.querySelector('[data-admin-bell-badge]');
                if (badge) {
                    badge.remove();
                    fetch('/dashboard/admin/notifikasi/baca', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Accept': 'application/json'
                        }
                    }).catch(() => {});
                }
                return;
            }

            if (!event.target.closest('.dashboard-admin-bell')) {
                dropdown.classList.remove('is-open');
            }
        });
    </script>
</body>

</html>
