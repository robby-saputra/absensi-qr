<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Kalender Sekolah</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-admin.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        @include('layouts.alerts')

        <div class="welcome">
            <div>
                <h2>Kalender Sekolah</h2>
                <p>Hari libur tidak akan dihitung sebagai alfa pada rekap dan verifikasi.</p>
            </div>
            <a href="/dashboard/admin/kalender-sekolah/create" class="btn">Tambah Kalender</a>
            <a href="/dashboard/admin/pdf/kalender" target="_blank" class="btn">PDF Resmi</a>
        </div>

        <form method="GET" class="panel admin-form">
            <label>
                Tahun Ajaran
                <select name="tahun_ajaran_id">
                    <option value="">Semua Tahun Ajaran</option>
                    @foreach ($tahunAjaran as $ta)
                        <option value="{{ $ta->id }}" {{ ($tahunAjaranId ?? '') == $ta->id ? 'selected' : '' }}>
                            {{ $ta->nama }} - {{ ucfirst($ta->semester) }} {{ $ta->aktif ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label>
                Provinsi
                <select name="provinsi">
                    <option value="">Semua Provinsi</option>
                    @foreach ($provinsiList as $prov)
                        <option value="{{ $prov }}" {{ ($provinsi ?? 'Banten') === $prov ? 'selected' : '' }}>
                            {{ $prov }} {{ $prov === 'Banten' ? '(Lokasi Anda)' : '' }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label>
                Bulan Kalender
                <input type="month" name="bulan" value="{{ $bulan }}">
            </label>
            <button type="submit" class="btn">Tampilkan</button>
        </form>

        <div class="panel">
            <div class="panel-head">
                <div>
                    <h3>Kalender Visual Bulanan</h3>
                    <p>{{ $monthStart->translatedFormat('F Y') }}</p>
                </div>
            </div>
            <div class="calendar-grid">
                @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $day)
                    <div class="calendar-head">{{ $day }}</div>
                @endforeach
                @php
                    $startPad = $monthStart->dayOfWeekIso - 1;
                    $daysInMonth = $monthStart->daysInMonth;
                @endphp
                @for ($i = 0; $i < $startPad; $i++)
                    <div class="calendar-cell muted"></div>
                @endfor
                @for ($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $date = $monthStart->copy()->day($day)->toDateString();
                        $events = $kalenderBulan->filter(
                            fn($item) => $item->tanggal_mulai <= $date && $item->tanggal_selesai >= $date,
                        );
                    @endphp
                    <div
                        class="calendar-cell {{ $events->where('jenis', 'libur')->isNotEmpty() ? 'libur' : ($events->where('jenis', 'ujian')->isNotEmpty() ? 'ujian' : ($events->where('jenis', 'kegiatan')->isNotEmpty() ? 'kegiatan' : '')) }}">
                        <strong>{{ $day }}</strong>
                        @foreach ($events->take(2) as $event)
                            <span>{{ $event->judul }}</span>
                        @endforeach
                    </div>
                @endfor
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <div>
                    <h3>Ringkasan Kalender</h3>
                    <p>Total event pada filter aktif.</p>
                </div>
            </div>
            <div class="cards">
                <div class="card">
                    <h3>Libur</h3>
                    <p>{{ $kalender->where('jenis', 'libur')->count() }}</p>
                </div>
                <div class="card">
                    <h3>Kegiatan</h3>
                    <p>{{ $kalender->where('jenis', 'kegiatan')->count() }}</p>
                </div>
                <div class="card">
                    <h3>Ujian</h3>
                    <p>{{ $kalender->where('jenis', 'ujian')->count() }}</p>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Judul</th>
                        <th>Jenis</th>
                        <th>Provinsi</th>
                        <th>Berulang</th>
                        <th>Sumber</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kalender as $item)
                        <tr>
                            <td>{{ $item->tanggal_mulai }} s/d {{ $item->tanggal_selesai }}</td>
                            <td>{{ $item->judul }}</td>
                            <td><span class="status-pill muted">{{ ucfirst($item->jenis) }}</span></td>
                            <td>{{ $item->provinsi ?? 'Umum' }}</td>
                            <td>{{ $item->berulang ?? false ? ucfirst($item->hari_berulang) : '-' }}</td>
                            <td>{{ ucfirst($item->sumber ?? 'manual') }}</td>
                            <td>{{ $item->keterangan ?? '-' }}</td>
                            <td class="action-buttons">
                                <a href="/dashboard/admin/kalender-sekolah/edit/{{ $item->id }}"
                                    class="btn edit">Edit</a>
                                <a href="/dashboard/admin/kalender-sekolah/delete/{{ $item->id }}"
                                    class="btn hapus"
                                    data-confirm="Data akan dihapus dari daftar utama.">Hapus</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-table">Belum ada data kalender sekolah.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>
