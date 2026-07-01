{{-- File ini menampilkan dashboard utama admin yang berisi ringkasan informasi penting sistem absensi QR. --}}
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

<body>

    @include('layouts.sidebar_admin')

    <main id="content" class="content">

        <div class="welcome">
            <div>
                <h2>Halo, {{ $user->nama }}</h2>
                <p>Ringkasan inti sistem absensi QR.</p>
            </div>
            <div class="dashboard-admin-bell">
                <button type="button" class="dashboard-admin-bell-btn" data-admin-bell aria-label="Notifikasi admin">
                    <i class="fa-solid fa-bell"></i>
                    @if (($adminBellUnread ?? 0) > 0)
                        <span data-admin-bell-badge>{{ $adminBellUnread > 99 ? '99+' : $adminBellUnread }}</span>
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
        </div>

        @include('layouts.libur_banner')

        <div class="period-banner">
            <div>
                <span>Periode Aktif</span>
                <strong>
                    {{ $tahunAjaranAktif ? $tahunAjaranAktif->nama . ' - ' . ucfirst($tahunAjaranAktif->semester) : 'Belum diatur' }}
                </strong>
            </div>
            <a href="/dashboard/admin/pengaturan">Ubah Periode Aktif</a>
        </div>

        @if (($kalenderHariIni ?? collect())->isNotEmpty())
            <div class="calendar-today-banner">
                <strong>Kalender Hari Ini</strong>
                @foreach ($kalenderHariIni as $event)
                    <span class="event-chip {{ $event->jenis }}">{{ ucfirst($event->jenis) }}:
                        {{ $event->judul }}</span>
                @endforeach
            </div>
        @endif

        <div class="cards">

            <div class="card">
                <h3>Siswa Aktif</h3>
                <p>{{ $totalSiswa }}</p>
            </div>

            <div class="card">
                <h3>Guru Aktif</h3>
                <p>{{ $totalGuru }}</p>
            </div>

            <div class="card">
                <h3>Total Kelas</h3>
                <p>{{ $totalKelas }}</p>
            </div>

            <div class="card">
                <h3>Total Jurusan</h3>
                <p>{{ $totalJurusan }}</p>
            </div>

        </div>

        <div class="overview">
            <div class="panel wide">
                <div class="panel-head">
                    <div>
                        <h3>Absensi Hari Ini</h3>
                        <p>Masuk, pulang, dan siswa yang belum absen.</p>
                    </div>
                </div>
                <canvas id="absensiChart" height="130"></canvas>
            </div>

            <div class="panel">
                <h3>Status Hari Ini</h3>
                <div class="metric-row">
                    <span>Masuk</span>
                    <strong>{{ $totalMasukHariIni }}</strong>
                </div>
                <div class="metric-row">
                    <span>Pulang</span>
                    <strong>{{ $totalPulangHariIni }}</strong>
                </div>
                <div class="metric-row danger">
                    <span>Belum Absen</span>
                    <strong>{{ $totalBelumAbsen }}</strong>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <div>
                    <h3>Jumlah Siswa Per Kelas</h3>
                    <p>Data mengikuti isi tabel siswa dan kelas terbaru.</p>
                </div>
            </div>
            <canvas id="kelasChart" height="150"></canvas>
        </div>

    </main>
    <script type="application/json" id="admin-chart-data">@json($chartData)</script>
    <script>
        const chartData = JSON.parse(
            document.getElementById('admin-chart-data')?.textContent || '{}'
        );

        function drawBarChart(canvasId, labels, values, colors) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) {
                return;
            }

            const ctx = canvas.getContext('2d');
            const width = canvas.width = canvas.offsetWidth;
            const height = canvas.height = Number(canvas.getAttribute('height')) || 150;
            const max = Math.max(...values, 1);
            const gap = 14;
            const barWidth = Math.max((width - gap * (values.length + 1)) / Math.max(values.length, 1), 24);

            ctx.clearRect(0, 0, width, height);
            ctx.font = '12px Arial';
            ctx.textAlign = 'center';

            values.forEach((value, index) => {
                const barHeight = Math.round((height - 48) * (value / max));
                const x = gap + index * (barWidth + gap);
                const y = height - barHeight - 28;

                ctx.fillStyle = colors[index % colors.length];
                ctx.fillRect(x, y, barWidth, barHeight);

                ctx.fillStyle = '#1f2937';
                ctx.fillText(value, x + barWidth / 2, y - 6);
                ctx.fillStyle = '#667085';
                ctx.fillText(labels[index], x + barWidth / 2, height - 8);
            });
        }

        function renderCharts() {
            drawBarChart('absensiChart', chartData.absensi?.labels || [], chartData.absensi?.values || [], ['#16a34a', '#2563eb',
                '#dc2626'
            ]);
            drawBarChart('kelasChart', chartData.kelas?.labels || [], chartData.kelas?.values || [], ['#273c75', '#16a34a', '#d97706',
                '#7c3aed'
            ]);
        }

        renderCharts();
        window.addEventListener('resize', renderCharts);

        document.addEventListener('click', function(event) {
            const button = event.target.closest('[data-admin-bell]');
            const dropdown = document.querySelector('[data-admin-bell-dropdown]');

            if (!dropdown) {
                return;
            }

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
    <style>
        .welcome {
            position: relative;
            z-index: 100;
            align-items: center;
            gap: 16px;
        }

        .period-banner,
        .calendar-today-banner,
        .cards,
        .overview,
        .panel {
            position: relative;
            z-index: 1;
        }

        .dashboard-admin-bell {
            position: relative;
            margin-left: auto;
            z-index: 3000;
        }

        .dashboard-admin-bell-btn {
            position: relative;
            width: 52px;
            height: 52px;
            border: 0;
            border-radius: 8px;
            color: #fff;
            background: #dc2626;
            box-shadow: 0 14px 28px rgba(220, 38, 38, .24);
            cursor: pointer;
            font-size: 20px;
        }

        .dashboard-admin-bell-btn:hover {
            background: #b91c1c;
        }

        .dashboard-admin-bell-btn span {
            position: absolute;
            top: -7px;
            right: -7px;
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            border: 2px solid #fff;
            border-radius: 999px;
            color: #dc2626;
            background: #fff;
            font-size: 12px;
            line-height: 18px;
            font-weight: 800;
        }

        .dashboard-admin-bell-dropdown {
            display: none;
            position: absolute;
            z-index: 4000;
            top: 64px;
            right: 0;
            width: 360px;
            max-width: calc(100vw - 36px);
            padding: 14px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 22px 48px rgba(15, 23, 42, .22);
        }

        .dashboard-admin-bell-dropdown.is-open {
            display: block;
        }

        .dashboard-admin-bell-dropdown strong {
            display: block;
            margin-bottom: 10px;
            font-size: 17px;
            line-height: 1.3;
            color: #111827;
        }

        .dashboard-admin-bell-dropdown a,
        .dashboard-admin-bell-dropdown p {
            display: block;
            margin: 0;
            padding: 14px 12px;
            border-radius: 8px;
            background: #fff;
            color: #374151;
            text-decoration: none;
            line-height: 1.45;
        }

        .dashboard-admin-bell-dropdown a:hover {
            background: #fef2f2;
        }

        .dashboard-admin-bell-dropdown a span {
            display: block;
            font-size: 16px;
            line-height: 1.35;
            font-weight: 700;
            color: #111827;
        }

        .dashboard-admin-bell-dropdown a small {
            display: block;
            margin-top: 5px;
            color: #6b7280;
            font-size: 14px;
            line-height: 1.45;
            white-space: normal;
            word-break: break-word;
        }

        .dashboard-admin-bell-dropdown p {
            font-size: 15px;
        }
    </style>
</body>

</html>
