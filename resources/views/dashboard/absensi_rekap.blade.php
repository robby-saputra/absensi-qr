<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Rekap Absensi Harian</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-absensi_rekap.css') }}">

</head>

<body>

    @include('layouts.sidebar_admin')

    <main id="content" class="content" data-print-title="Rekap Absensi Harian"
        data-print-date="{{ now()->format('d-m-Y H:i') }}">

        <div class="container">

            <div class="top">

                <h2>
                    Rekap Absensi Harian
                </h2>

                <a href="{{ route('dashboard.admin') }}" class="btn">

                    Kembali

                </a>

                <button type="button" class="btn" onclick="printReport('Rekap Absensi Harian')">Print</button>
                <button type="button" class="btn export"
                    onclick="exportTableToExcel('rekap-absensi-harian', 'Rekap Absensi Harian')">Excel</button>
                <a class="btn" target="_blank"
                    href="/dashboard/admin/rekap/absensi-pdf?mode={{ $filters['mode'] ?? '' }}&tanggal={{ $filters['tanggal'] ?? '' }}&bulan={{ $filters['bulan'] ?? '' }}&tahun_ajaran_id={{ $filters['tahun_ajaran_id'] ?? '' }}">PDF
                    Resmi</a>
                <a class="btn" href="/dashboard/admin/absensi/create">Tambah Absensi</a>

            </div>

            <div class="info-box">
                Sumber data: tabel absensi harian dari guru piket. Halaman ini bukan rekap absen mapel.
                Kolom masuk dan pulang di bawah adalah absensi harian siswa di sekolah.
            </div>

            @if (!empty($libur))
                <div class="info-box">
                    Tanggal ini masuk kalender libur: <strong>{{ $libur->judul }}</strong>.
                    Siswa yang tidak absen pada tanggal libur tidak dihitung alfa.
                </div>
            @endif



            <form method="GET" action="{{ route('rekap.absensi') }}" class="filter">


                <div>

                    <label>
                        Tahun Ajaran
                    </label>

                    <br>

                    <select name="tahun_ajaran_id">
                        <option value="">Semua Tahun Ajaran</option>
                        @foreach ($tahunAjaran as $ta)
                            <option value="{{ $ta->id }}"
                                {{ ($filters['tahun_ajaran_id'] ?? '') == $ta->id ? 'selected' : '' }}>
                                {{ $ta->nama }} - {{ ucfirst($ta->semester) }} {{ $ta->aktif ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>

                </div>



                <div>

                    <label>Mode</label>

                    <br>

                    <select name="mode">

                        <option value="tanggal" {{ ($filters['mode'] ?? '') == 'tanggal' ? 'selected' : '' }}>

                            Per Tanggal

                        </option>



                        <option value="bulan" {{ ($filters['mode'] ?? '') == 'bulan' ? 'selected' : '' }}>

                            Per Bulan

                        </option>

                    </select>

                </div>



                <div>

                    <label>
                        Tanggal
                    </label>

                    <br>

                    <input type="date" name="tanggal" value="{{ $filters['tanggal'] ?? '' }}">

                </div>




                <div>

                    <label>
                        Bulan
                    </label>

                    <br>

                    <input type="month" name="bulan" value="{{ $filters['bulan'] ?? '' }}">

                </div>




                <button type="submit" class="btn">

                    Tampilkan

                </button>




                <a href="{{ route('export.absensi', [
                    'mode' => $filters['mode'] ?? '',
                    'tanggal' => $filters['tanggal'] ?? '',
                    'bulan' => $filters['bulan'] ?? '',
                    'tahun_ajaran_id' => $filters['tahun_ajaran_id'] ?? '',
                ]) }}"
                    class="btn export">

                    Export Excel (.xlsx)

                </a>

            </form>

            <form method="POST" action="/dashboard/admin/auto-alfa" class="filter">
                @csrf
                <input type="hidden" name="mode" value="{{ $filters['mode'] ?? 'tanggal' }}">
                <input type="hidden" name="bulan" value="{{ $filters['bulan'] ?? now()->format('Y-m') }}">
                <input type="hidden" name="tahun_ajaran_id" value="{{ $filters['tahun_ajaran_id'] ?? '' }}">
                <div>
                    <label>Auto Alfa Tanggal</label>
                    <br>
                    <input type="date" name="tanggal" value="{{ $filters['tanggal'] ?? now()->toDateString() }}">
                </div>
                <button type="submit" class="btn">Auto Alfa</button>
            </form>





            <table>

                <tr>

                    <th>Tanggal</th>

                    <th>Nama</th>

                    <th>NIS</th>

                    <th>Kelas</th>

                    <th>Absen Harian Masuk</th>

                    <th>Absen Harian Pulang</th>

                    <th>Status Siswa</th>

                    <th>Aksi</th>

                </tr>



                @forelse($absensi as $a)
                    @php
                        $statusKhusus = in_array($a->status_masuk, ['izin', 'sakit'])
                            ? $a->status_masuk
                            : (in_array($a->status_pulang, ['izin', 'sakit'])
                                ? $a->status_pulang
                                : null);

                        $statusMasuk = $statusKhusus ?? $a->status_masuk;
                        $statusPulang = $statusKhusus ?? $a->status_pulang;
                        $statusSiswa = !empty($libur)
                            ? 'libur'
                            : $statusKhusus ??
                                ((in_array($a->status_masuk, ['telat', 'terlambat']) ? 'telat' : null) ??
                                    ($a->status_masuk ? 'hadir' : $filters['status_default_alfa'] ?? 'alfa'));
                    @endphp

                    <tr>

                        <td>

                            {{ $a->tanggal }}

                        </td>


                        <td>

                            {{ $a->nama }}

                        </td>


                        <td>

                            {{ $a->nis ?? '-' }}

                        </td>


                        <td>

                            {{ $a->nama_kelas ?? '-' }}

                        </td>


                        <td>

                            {{ $statusMasuk ? ($a->jam_masuk ? $a->jam_masuk . ' - ' : '') . $statusMasuk : $a->jam_masuk ?? '-' }}

                        </td>


                        <td>

                            {{ $statusPulang ? ($a->jam_pulang ? $a->jam_pulang . ' - ' : '') . $statusPulang : $a->jam_pulang ?? '-' }}

                        </td>


                        <td>

                            {{ ucfirst($statusSiswa) }}

                        </td>

                        <td>
                            <a class="btn" href="/dashboard/admin/absensi/{{ $a->id }}">Lihat</a>
                            <a class="btn" href="/dashboard/admin/absensi/edit/{{ $a->id }}">Ubah</a>
                            <a class="btn confirm-delete"
                                href="/dashboard/admin/absensi/delete/{{ $a->id }}">Hapus</a>
                        </td>

                    </tr>



                @empty


                    <tr>

                        <td colspan="8">

                            Data absensi tidak tersedia

                        </td>

                    </tr>
                @endforelse


            </table>


        </div>


    </main>

</body>

</html>
