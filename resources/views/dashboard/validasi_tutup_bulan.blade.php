<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Validasi Tutup Bulan</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/admin-polish.css') }}?v=20260602-polish">
</head>

<body>
    @if (($user->role ?? '') === 'admin')
        @include('layouts.sidebar_admin')
    @else
        @include('layouts.sidebar_wali')
    @endif
    <main id="content" class="content admin-polish">
        <div class="polish-hero">
            <div><span class="polish-kicker">Validasi Bulanan</span>
                <h1>Validasi Tutup Bulan</h1>
                <p>Periksa data belum lengkap sebelum laporan bulan {{ $bulan }} dikunci.</p>
            </div>
            <a class="polish-btn secondary"
                href="{{ ($user->role ?? '') === 'admin' ? '/dashboard/admin' : '/dashboard/wali' }}">Kembali</a>
        </div>

        <div class="polish-stats">
            <div
                class="polish-stat {{ ($statusBulanan->status ?? '') === 'valid' ? 'success' : (($statusBulanan->status ?? '') === 'dikunci' ? 'success' : 'warn') }}">
                <span>Status Bulan</span><strong
                    style="font-size:22px">{{ ucwords(str_replace('_', ' ', $statusBulanan->status ?? 'belum_dicek')) }}</strong>
            </div>
            <div class="polish-stat {{ ($statusBulanan->total_masalah ?? 0) > 0 ? 'danger' : 'success' }}"><span>Total
                    Masalah</span><strong>{{ $statusBulanan->total_masalah ?? 0 }}</strong></div>
            <div class="polish-stat"><span>Terakhir Dicek</span><strong
                    style="font-size:18px">{{ $statusBulanan?->checked_at ? \Carbon\Carbon::parse($statusBulanan->checked_at)->format('d-m-Y H:i') : '-' }}</strong>
            </div>
            <div class="polish-stat"><span>Dikunci</span><strong
                    style="font-size:18px">{{ $statusBulanan?->locked_at ? \Carbon\Carbon::parse($statusBulanan->locked_at)->format('d-m-Y H:i') : 'Belum' }}</strong>
            </div>
        </div>

        <form method="GET" class="polish-filter">
            <label>Bulan <input type="month" name="bulan" value="{{ $bulan }}"></label>
            <label>Tahun Ajaran
                <select name="tahun_ajaran_id">
                    @foreach ($tahunAjaran as $ta)
                        <option value="{{ $ta->id }}"
                            {{ (string) $tahunAjaranId === (string) $ta->id ? 'selected' : '' }}>{{ $ta->nama }} -
                            {{ ucfirst($ta->semester) }}</option>
                    @endforeach
                </select>
            </label>
            @if (($user->role ?? '') === 'admin')
                <label>Kelas
                    <select name="kelas_id">
                        <option value="">Semua Kelas</option>
                        @foreach ($kelas as $k)
                            <option value="{{ $k->id }}"
                                {{ (string) $kelasId === (string) $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}
                            </option>
                        @endforeach
                    </select>
                </label>
            @endif
            <button class="polish-btn">Cek Data</button>
        </form>

        @if (($statusBulanan->status ?? '') === 'valid')
            <form method="POST" action="/dashboard/validasi-tutup-bulan/kunci" class="polish-filter">
                @csrf
                <input type="hidden" name="bulan" value="{{ $bulan }}">
                <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaranId }}">
                <input type="hidden" name="kelas_id" value="{{ $kelasId }}">
                <label>Catatan Kunci Bulan <input name="catatan"
                        placeholder="Opsional, contoh: data sudah diperiksa wali kelas"></label>
                <button class="polish-btn success"
                    data-confirm="Kunci laporan bulan {{ $bulan }}? Setelah dikunci data rekap tidak boleh berubah sembarangan.">Kunci
                    Bulan Ini</button>
            </form>
        @elseif(($statusBulanan->status ?? '') === 'dikunci')
            <div class="polish-alert success">Laporan bulan ini sudah dikunci.</div>
        @else
            <div class="polish-alert">Masih ada data yang perlu diperbaiki sebelum bulan bisa dikunci.</div>
        @endif

        <div class="polish-stats">
            <div class="polish-stat warn"><span>Belum
                    Pulang</span><strong>{{ $hasil['belumPulang']->count() }}</strong></div>
            <div class="polish-stat warn"><span>Belum Absen
                    Mapel</span><strong>{{ $hasil['belumMapel']->count() }}</strong></div>
            <div class="polish-stat danger">
                <span>Alfa</span><strong>{{ $hasil['alfaBelumDiproses']->count() }}</strong></div>
            <div class="polish-stat warn"><span>Izin
                    Menunggu</span><strong>{{ $hasil['izinBelumReview']->count() }}</strong></div>
        </div>

        @foreach ([
        'belumPulang' => ['Siswa Belum Absen Pulang', ['Tanggal', 'Nama', 'Kelas', 'Jam Masuk', 'Aksi']],
        'belumMapel' => ['Siswa Belum Absen Mapel', ['Hari', 'Nama', 'Kelas', 'Mapel', 'Jam', 'Aksi']],
        'alfaBelumDiproses' => ['Data Alfa', ['Tanggal', 'Nama', 'Kelas', 'Status Masuk', 'Status Pulang', 'Aksi']],
        'izinBelumReview' => ['Pengajuan Izin/Sakit Belum Direview', ['Tanggal', 'Nama', 'Kelas', 'Jenis', 'Aksi']],
    ] as $key => [$judul, $headers])
            <section class="polish-panel">
                <div class="polish-panel-head">
                    <div>
                        <h2>{{ $judul }}</h2>
                        <p>{{ $hasil[$key]->count() }} temuan perlu dicek.</p>
                    </div><span class="polish-badge">{{ $hasil[$key]->count() }} data</span>
                </div>
                <table class="polish-table">
                    <tr>
                        @foreach ($headers as $h)
                            <th>{{ $h }}</th>
                        @endforeach
                    </tr>
                    @forelse($hasil[$key] as $row)
                        <tr>
                            @if ($key === 'belumPulang')
                                <td>{{ $row->tanggal }}</td>
                                <td>{{ $row->nama }}</td>
                                <td>{{ $row->nama_kelas ?? '-' }}</td>
                                <td>{{ $row->jam_masuk }}</td>
                                <td><a class="btn" href="/dashboard/admin/absensi/edit/{{ $row->id }}">Edit
                                        Absensi</a></td>
                            @elseif($key === 'belumMapel')
                                <td>{{ $row->hari }}</td>
                                <td>{{ $row->nama }}</td>
                                <td>{{ $row->nama_kelas ?? '-' }}</td>
                                <td>{{ $row->nama_mapel ?? '-' }}</td>
                                <td>{{ $row->jam_mulai }} - {{ $row->jam_selesai }}</td>
                                <td><a class="btn" href="/dashboard/admin/absensi-mapel/create">Input Mapel</a></td>
                            @elseif($key === 'alfaBelumDiproses')
                                <td>{{ $row->tanggal }}</td>
                                <td>{{ $row->nama }}</td>
                                <td>{{ $row->nama_kelas ?? '-' }}</td>
                                <td>{{ $row->status_masuk ?? '-' }}</td>
                                <td>{{ $row->status_pulang ?? '-' }}</td>
                                <td><a class="btn" href="/dashboard/admin/absensi/edit/{{ $row->id }}">Review
                                        Alfa</a></td>
                            @else
                                <td>{{ $row->tanggal_mulai }} s/d {{ $row->tanggal_selesai }}</td>
                                <td>{{ $row->nama }}</td>
                                <td>{{ $row->nama_kelas ?? '-' }}</td>
                                <td>{{ ucfirst($row->jenis) }}</td>
                                <td><a class="btn" href="/dashboard/admin/pengajuan-izin">Review Izin</a></td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($headers) }}" class="polish-empty">Aman, tidak ada data bermasalah.
                            </td>
                        </tr>
                    @endforelse
                </table>
            </section>
        @endforeach
    </main>
</body>

</html>
