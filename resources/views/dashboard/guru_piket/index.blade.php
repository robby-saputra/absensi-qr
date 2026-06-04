<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Data Guru Piket</title>
    <link rel="stylesheet"
        href="{{ asset('css/pages/dashboard-guru_piket-index.css') }}?v={{ filemtime(public_path('css/pages/dashboard-guru_piket-index.css')) }}">
</head>

<body>
    @include('layouts.sidebar_admin')
    <style>
        .piket-hero h1 {
            color: #fff !important;
        }
    </style>

    <main id="content" class="content">
        <section class="piket-page">
            @include('layouts.alerts')

            <div class="piket-hero">
                <div>
                    <span class="eyebrow">Jadwal Tim</span>
                    <h1>Guru Piket</h1>
                    <p>Satu kartu mewakili satu tim piket berdasarkan hari dan jam tugas.</p>
                </div>

                <div class="hero-actions">
                    <a href="/dashboard/admin" class="btn btn-ghost">Kembali</a>
                    <a href="/dashboard/admin/guru-piket/create" class="btn btn-primary">Tambah Guru</a>
                    <a href="/dashboard/admin/pdf/guru-piket" target="_blank" class="btn btn-soft">PDF Resmi</a>
                </div>
            </div>

            <div class="summary-grid">
                <div class="summary-card">
                    <span>Total Tim</span>
                    <strong>{{ $ringkasanPiket['tim'] ?? 0 }}</strong>
                </div>
                <div class="summary-card">
                    <span>Total Guru</span>
                    <strong>{{ $ringkasanPiket['guru'] ?? 0 }}</strong>
                </div>
                <div class="summary-card">
                    <span>Tim Aktif</span>
                    <strong>{{ $ringkasanPiket['aktif'] ?? 0 }}</strong>
                </div>
            </div>

            <form method="GET" class="filter-card">
                <label for="hari">Filter hari</label>
                <select name="hari" id="hari">
                    <option value="">Semua Hari</option>
                    @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h)
                        <option value="{{ $h }}" {{ ($hari ?? '') == $h ? 'selected' : '' }}>
                            {{ $h }}
                        </option>
                    @endforeach
                </select>
                <button class="btn btn-primary" type="submit">Tampilkan</button>
            </form>

            <div class="team-grid">
                @forelse($timPiket as $tim)
                    <article class="team-card" style="--delay: {{ $loop->index * 70 }}ms">
                        <div class="team-card-header">
                            <div>
                                <span class="day-badge">{{ $tim->hari_label }}</span>
                                <h2>Tim Piket {{ $tim->hari_label }}</h2>
                                <p>
                                    @if ($tim->jam_mulai && $tim->jam_selesai)
                                        {{ substr($tim->jam_mulai, 0, 5) }} - {{ substr($tim->jam_selesai, 0, 5) }}
                                    @else
                                        Jam belum diatur
                                    @endif
                                </p>
                            </div>
                            <span class="team-status {{ $tim->status_class }}">{{ $tim->status }}</span>
                        </div>

                        <div class="team-meter">
                            <span style="width: {{ min(100, ($tim->jumlah / 5) * 100) }}%"></span>
                        </div>

                        <div class="team-count">
                            <strong>{{ $tim->jumlah }}</strong>
                            <span>dari 5 guru dalam tim</span>
                        </div>

                        <div class="member-list">
                            @foreach ($tim->anggota as $anggota)
                                @php
                                    $inisial = collect(explode(' ', trim($anggota->nama)))
                                        ->filter()
                                        ->take(2)
                                        ->map(fn($nama) => strtoupper(substr($nama, 0, 1)))
                                        ->implode('');

                                    $statusClass = match ($anggota->status) {
                                        'Sedang Bertugas' => 'sedang',
                                        'Izin' => 'izin',
                                        'Sakit' => 'sakit',
                                        'Selesai' => 'selesai',
                                        default => 'akan',
                                    };
                                @endphp

                                <div class="member-card">
                                    <div class="avatar">{{ $inisial ?: 'GP' }}</div>
                                    <div class="member-main">
                                        <strong>{{ $anggota->nama }}</strong>
                                        <span
                                            class="member-status {{ $statusClass }}">{{ $anggota->status ?: 'Akan Bertugas' }}</span>
                                    </div>
                                    <div class="member-actions">
                                        <a href="/dashboard/admin/guru-piket/edit/{{ $anggota->id }}"
                                            class="mini-btn edit">Edit</a>
                                        <a href="/dashboard/admin/guru-piket/delete/{{ $anggota->id }}"
                                            class="mini-btn hapus"
                                            data-confirm="Data akan dihapus dari daftar utama.">
                                            Hapus
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <div class="empty-state">
                        <strong>Belum ada tim guru piket</strong>
                        <span>Tambahkan guru piket, nanti jadwal yang hari dan jamnya sama akan otomatis tampil sebagai
                            satu tim.</span>
                        <a href="/dashboard/admin/guru-piket/create" class="btn btn-primary">Tambah Guru Piket</a>
                    </div>
                @endforelse
            </div>
        </section>
    </main>
</body>

</html>
