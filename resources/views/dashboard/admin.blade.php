<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-admin.css') }}">
</head>

<body>

    @include('layouts.sidebar_admin')

    <main id="content" class="content">

        <div class="welcome">
            <div>
                <h2>Halo, {{ $user->nama }}</h2>
                <p>Selamat datang di dashboard admin</p>
            </div>

            <a href="/dashboard/admin/notifikasi" class="notif-button">
                Notifikasi
                <span>{{ $totalNotifikasi }}</span>
            </a>
        </div>

        @include('layouts.libur_banner')

        <div class="period-banner">
            <div>
                <span>Periode Aktif</span>
                <strong>
                    {{ $tahunAjaranAktif ? $tahunAjaranAktif->nama . ' - ' . ucfirst($tahunAjaranAktif->semester) : 'Belum diatur' }}
                </strong>
            </div>
            <a href="/dashboard/admin/tahun-ajaran">Kelola Tahun Ajaran</a>
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
                <h3>Siswa Nonaktif</h3>
                <p>{{ $totalSiswaNonaktif ?? 0 }}</p>
                <a href="/dashboard/admin/siswa?status=nonaktif">Lihat</a>
            </div>

            <div class="card">
                <h3>Guru Aktif</h3>
                <p>{{ $totalGuru }}</p>
            </div>

            <div class="card">
                <h3>Guru Nonaktif</h3>
                <p>{{ $totalGuruNonaktif ?? 0 }}</p>
                <a href="/dashboard/admin/guru?status=nonaktif">Lihat</a>
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
                <h3>Guru Piket Hari Ini</h3>
                <div class="metric-row">
                    <span>Bertugas</span>
                    <strong>{{ $guruPiketAktif }}</strong>
                </div>
                <div class="metric-row danger">
                    <span>Izin/Sakit</span>
                    <strong>{{ $guruPiketTidakHadir }}</strong>
                </div>
                <canvas id="piketChart" height="120"></canvas>
            </div>
        </div>

        <div class="attention-panel">
            <div class="panel-head">
                <div>
                    <h3>Perlu Perhatian</h3>
                    <p>Ringkasan 30 hari terakhir dan kondisi operasional hari ini.</p>
                </div>
                <div class="attention-score">
                    {{ array_sum($perluPerhatian ?? []) }}
                    <span>temuan</span>
                </div>
            </div>

            <div class="attention-grid">
                <div class="attention-card">
                    <div class="attention-card-head">
                        <h4>Siswa Sering Telat</h4>
                        <a href="/dashboard/admin/rekap/absensi?status=telat">Rekap</a>
                    </div>
                    @forelse ($topSiswaTelat as $item)
                        <div class="attention-item">
                            <div>
                                <strong>{{ $item->nama }}</strong>
                                <span>{{ $item->nama_kelas ?? '-' }} | NIS {{ $item->nis ?? '-' }}</span>
                            </div>
                            <b>{{ $item->total }}x</b>
                        </div>
                    @empty
                        <div class="attention-empty">Tidak ada siswa sering telat.</div>
                    @endforelse
                </div>

                <div class="attention-card">
                    <div class="attention-card-head">
                        <h4>Siswa Sering Alfa</h4>
                        <a href="/dashboard/admin/rekap/absensi?status=alfa">Rekap</a>
                    </div>
                    @forelse ($topSiswaAlfa as $item)
                        <div class="attention-item danger">
                            <div>
                                <strong>{{ $item->nama }}</strong>
                                <span>{{ $item->nama_kelas ?? '-' }} | NIS {{ $item->nis ?? '-' }}</span>
                            </div>
                            <b>{{ $item->total }}x</b>
                        </div>
                    @empty
                        <div class="attention-empty">Tidak ada siswa sering alfa.</div>
                    @endforelse
                </div>

                <div class="attention-card">
                    <div class="attention-card-head">
                        <h4>Pengajuan Menunggu</h4>
                        <a href="/dashboard/admin/pengajuan-izin">Buka</a>
                    </div>
                    <div class="attention-big {{ ($pengajuanMenunggu ?? 0) > 0 ? 'warning' : '' }}">
                        {{ $pengajuanMenunggu ?? 0 }}
                    </div>
                    <p>Pengajuan izin/sakit yang belum direview.</p>
                </div>

                <div class="attention-card">
                    <div class="attention-card-head">
                        <h4>Guru Piket Tidak Hadir</h4>
                        <a href="/dashboard/admin/guru-piket">Buka</a>
                    </div>
                    @forelse ($guruPiketTidakHadirList as $item)
                        <div class="attention-item danger">
                            <div>
                                <strong>{{ $item->nama_guru }}</strong>
                                <span>{{ ucfirst($item->status) }} | {{ $item->jam_mulai }}-{{ $item->jam_selesai }}</span>
                                <small>Pengganti: {{ collect([$item->pengganti_1, $item->pengganti_2])->filter()->implode(', ') ?: '-' }}</small>
                            </div>
                            <b>{{ ucfirst($item->hari) }}</b>
                        </div>
                    @empty
                        <div class="attention-empty">Tidak ada guru piket izin/sakit hari ini.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="panel online-panel">
            <div class="panel-head">
                <div>
                    <h3>User Login Realtime</h3>
                    <p>Daftar user yang sedang aktif tanpa refresh halaman.</p>
                </div>
                <div class="online-badge">
                    <span id="onlineTotal">0</span>
                    Online
                </div>
            </div>

            <div class="online-meta">
                Update terakhir: <strong id="onlineCheckedAt">-</strong>
            </div>

            <div class="online-list" id="onlineUsers">
                <div class="online-empty">Memuat data login...</div>
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
    <script>
        const chartData = @json($chartData);

        function drawBarChart(canvasId, labels, values, colors) {
            const canvas = document.getElementById(canvasId);
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

        function drawDonutChart(canvasId, labels, values, colors) {
            const canvas = document.getElementById(canvasId);
            const ctx = canvas.getContext('2d');
            const width = canvas.width = canvas.offsetWidth;
            const height = canvas.height = Number(canvas.getAttribute('height')) || 120;
            const total = values.reduce((sum, value) => sum + value, 0) || 1;
            const centerX = width / 2;
            const centerY = height / 2;
            const radius = Math.min(width, height) / 2 - 10;
            let start = -Math.PI / 2;

            ctx.clearRect(0, 0, width, height);

            values.forEach((value, index) => {
                const angle = (value / total) * Math.PI * 2;
                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, start, start + angle);
                ctx.closePath();
                ctx.fillStyle = colors[index % colors.length];
                ctx.fill();
                start += angle;
            });

            ctx.beginPath();
            ctx.arc(centerX, centerY, radius * .56, 0, Math.PI * 2);
            ctx.fillStyle = '#fff';
            ctx.fill();
            ctx.fillStyle = '#1f2937';
            ctx.font = 'bold 18px Arial';
            ctx.textAlign = 'center';
            ctx.fillText(values[0], centerX, centerY + 6);
        }

        function renderCharts() {
            drawBarChart('absensiChart', chartData.absensi.labels, chartData.absensi.values, ['#16a34a', '#2563eb',
                '#dc2626'
            ]);
            drawBarChart('kelasChart', chartData.kelas.labels, chartData.kelas.values, ['#273c75', '#16a34a', '#d97706',
                '#7c3aed'
            ]);
            drawDonutChart('piketChart', chartData.piket.labels, chartData.piket.values, ['#16a34a', '#dc2626']);
        }

        renderCharts();
        window.addEventListener('resize', renderCharts);

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            } [char]));
        }

        async function loadOnlineUsers() {
            const list = document.getElementById('onlineUsers');
            const total = document.getElementById('onlineTotal');
            const checkedAt = document.getElementById('onlineCheckedAt');

            try {
                const response = await fetch('/dashboard/admin/online-users', {
                    headers: {
                        'Accept': 'application/json'
                    },
                    cache: 'no-store',
                });

                if (!response.ok) {
                    throw new Error('Gagal mengambil data');
                }

                const data = await response.json();
                total.textContent = data.total;
                checkedAt.textContent = data.checked_at;

                if (!data.users.length) {
                    list.innerHTML = '<div class="online-empty">Belum ada user yang aktif.</div>';
                    return;
                }

                list.innerHTML = data.users.map((item) => `
            <div class="online-item">
                <span class="online-dot"></span>
                <div>
                    <strong>${escapeHtml(item.nama)}</strong>
                    <small>${escapeHtml(item.kelas && item.kelas !== '-' ? `${item.role} | ${item.kelas}` : item.role)}</small>
                </div>
                <div class="online-time">
                    <span>Login ${escapeHtml(item.login_at)}</span>
                    <small>${escapeHtml(item.last_seen_at)}</small>
                </div>
            </div>
        `).join('');
            } catch (error) {
                list.innerHTML = '<div class="online-empty error">Data online belum bisa dimuat.</div>';
            }
        }

        loadOnlineUsers();
        setInterval(loadOnlineUsers, 5000);
    </script>
</body>

</html>
