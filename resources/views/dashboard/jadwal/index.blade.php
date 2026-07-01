{{-- File ini menampilkan daftar jadwal pelajaran sebagai acuan proses absensi dan pembagian kelas. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jadwal Pelajaran</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-jadwal-index.css') }}">
</head>
<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        @php
            $totalKelas = $jadwal->pluck('kelas_id')->filter()->unique()->count();
            $totalGuru = $jadwal->pluck('guru_id')->filter()->unique()->count();
            $perluPengganti = $jadwal->filter(fn ($item) => ($item->status_guru ?: 'normal') !== 'normal')->count();
            $hariUrut = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
            $hariIni = strtolower(now()->locale('id')->translatedFormat('l'));
            $posisiHariIni = array_search($hariIni, $hariUrut, true);
            $hariMulaiHariIni = $posisiHariIni === false ? $hariUrut : array_merge(array_slice($hariUrut, $posisiHariIni), array_slice($hariUrut, 0, $posisiHariIni));
        @endphp

        <div class="schedule-page">
            <section class="schedule-hero">
                <div class="hero-copy">
                    <span class="eyebrow">Administrasi Akademik</span>
                    <h1>Jadwal Pelajaran</h1>
                    <p>Atur sesi mengajar, jam pelajaran, dan penugasan guru dalam satu tampilan.</p>
                </div>
                <div class="hero-actions">
                    <a class="btn btn-ghost" href="/dashboard/admin">Kembali</a>
                    <a class="btn btn-soft" target="_blank" href="/dashboard/admin/pdf/jadwal">PDF Resmi</a>
                    <a class="btn btn-primary" href="/dashboard/admin/jadwal/create">
                        <span aria-hidden="true">＋</span> Tambah Jadwal
                    </a>
                </div>
            </section>

            @include('layouts.alerts')

            <section class="summary-grid" aria-label="Ringkasan jadwal">
                <article class="summary-card blue">
                    <div class="summary-icon">JP</div>
                    <div><span>Total Sesi</span><strong>{{ $jadwal->count() }}</strong></div>
                </article>
                <article class="summary-card green">
                    <div class="summary-icon">K</div>
                    <div><span>Kelas Terjadwal</span><strong>{{ $totalKelas }}</strong></div>
                </article>
                <article class="summary-card violet">
                    <div class="summary-icon">G</div>
                    <div><span>Guru Mengajar</span><strong>{{ $totalGuru }}</strong></div>
                </article>
                <article class="summary-card amber">
                    <div class="summary-icon">!</div>
                    <div><span>Perlu Pengganti</span><strong>{{ $perluPengganti }}</strong></div>
                </article>
            </section>

            <section class="schedule-panel">
                <div class="panel-head">
                    <div>
                        <span class="section-kicker">Daftar Jadwal</span>
                        <h2>Seluruh sesi pelajaran</h2>
                        <p><span id="visibleCount">{{ $jadwal->count() }}</span> dari {{ $jadwal->count() }} jadwal ditampilkan</p>
                    </div>
                    <div class="filters">
                        <label class="search-box">
                            <span aria-hidden="true">⌕</span>
                            <input id="scheduleSearch" type="search" placeholder="Cari kelas, mapel, atau guru..." autocomplete="off">
                        </label>
                        <select id="statusFilter" aria-label="Filter status guru">
                            <option value="">Semua status</option>
                            <option value="normal">Guru hadir</option>
                            <option value="pengganti">Perlu pengganti</option>
                        </select>
                    </div>
                </div>

                <nav class="day-tabs" aria-label="Pilih hari jadwal">
                    @foreach($hariMulaiHariIni as $hari)
                        @php $jumlahHari = $jadwal->filter(fn ($item) => strtolower($item->hari) === $hari)->count(); @endphp
                        <button type="button" class="day-tab {{ $hari === $hariIni ? 'is-active' : '' }}"
                            data-day="{{ $hari }}" aria-pressed="{{ $hari === $hariIni ? 'true' : 'false' }}">
                            <span>{{ ucfirst($hari) }}</span>
                            @if($hari === $hariIni)<small>Hari ini</small>@endif
                            <b>{{ $jumlahHari }}</b>
                        </button>
                    @endforeach
                </nav>

                <div class="table-scroll">
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Hari &amp; Kelas</th>
                                <th>Jam Pelajaran</th>
                                <th>Mata Pelajaran</th>
                                <th>Guru Bertugas</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                                <th class="action-heading">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="scheduleRows">
                            @forelse ($jadwal as $j)
                                @php
                                    $statusGuru = $j->status_guru_harian ?? 'normal';
                                    $isNormal = $statusGuru === 'normal';
                                    $searchText = strtolower(implode(' ', [
                                        $j->nama_kelas, $j->hari, $j->nama_mapel,
                                        $j->nama_guru, $j->nama_guru_pengganti ?? '',
                                        $j->keterangan ?? '',
                                    ]));
                                    $inisial = collect(explode(' ', trim($j->nama_guru)))
                                        ->filter()->take(2)->map(fn ($kata) => strtoupper(substr($kata, 0, 1)))->implode('');
                                @endphp
                                <tr class="schedule-row" data-search="{{ $searchText }}"
                                    data-day="{{ strtolower($j->hari) }}"
                                    data-status="{{ $isNormal ? 'normal' : 'pengganti' }}">
                                    <td data-label="Hari & Kelas">
                                        <div class="day-class">
                                            <span class="day-pill">{{ ucfirst($j->hari) }}</span>
                                            <strong>{{ $j->nama_kelas }}</strong>
                                        </div>
                                    </td>
                                    <td data-label="Jam Pelajaran">
                                        <div class="jp-cell">
                                            <strong>{{ labelJadwalJp($j, false) }}</strong>
                                            <span>{{ substr($j->jam_mulai, 0, 5) }}–{{ substr($j->jam_selesai, 0, 5) }}</span>
                                        </div>
                                    </td>
                                    <td data-label="Mata Pelajaran">
                                        <strong class="subject-name">{{ $j->nama_mapel }}</strong>
                                    </td>
                                    <td data-label="Guru Bertugas">
                                        <div class="teacher-cell">
                                            <span class="avatar">{{ $inisial ?: 'GR' }}</span>
                                            <div>
                                                <strong>{{ $j->nama_guru }}</strong>
                                                <small>Guru aktif: {{ $j->nama_guru_aktif ?? 'Belum tersedia' }}</small>
                                                <small>Pengganti terbaru: {{ $j->nama_guru_pengganti ?? 'Belum ditentukan' }}</small>
                                                @if(($j->replacement_chain ?? collect())->isNotEmpty())
                                                    <small>Rantai: @foreach($j->replacement_chain as $r)#{{ $r->urutan_penggantian }} {{ $r->nama_pengganti }} ({{ ucfirst(str_replace('_',' ',$r->status_penugasan)) }})@if(!$loop->last), @endif @endforeach</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Status">
                                        <span class="status-badge {{ $isNormal ? 'is-normal' : 'is-replacement' }}">
                                            <i></i>{{ $isNormal ? 'Guru hadir' : ucfirst($statusGuru) }}
                                        </span>
                                        @if ($j->alasan_tidak_hadir)
                                            <small class="status-note">{{ $j->alasan_tidak_hadir }}</small>
                                        @endif
                                    </td>
                                    <td data-label="Keterangan">
                                        <span class="description {{ empty($j->keterangan) ? 'is-empty' : '' }}">
                                            {{ $j->keterangan ?? 'Tidak ada catatan' }}
                                        </span>
                                    </td>
                                    <td data-label="Aksi">
                                        <div class="row-actions">
                                            <a class="icon-btn edit" href="/dashboard/admin/jadwal/edit/{{ $j->id }}" title="Edit jadwal">Edit</a>
                                            @if($j->needs_replacement ?? false)
                                                <a class="icon-btn edit" href="/dashboard/admin/jadwal/{{ $j->id }}/replacement?tanggal={{ now('Asia/Jakarta')->toDateString() }}" title="Tugaskan pengganti">{{ ($j->replacement_chain ?? collect())->isEmpty() ? 'Tugaskan Pengganti' : 'Tugaskan Pengganti Lanjutan' }}</a>
                                            @endif
                                            <a class="icon-btn delete" href="/dashboard/admin/jadwal/delete/{{ $j->id }}"
                                                data-confirm="Data akan dihapus dari daftar utama." title="Hapus jadwal">Hapus</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7"><div class="empty-state"><strong>Belum ada jadwal pelajaran</strong><span>Tambahkan jadwal pertama untuk mulai menyusun kegiatan belajar.</span></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div id="filterEmpty" class="empty-state" hidden>
                    <strong>Jadwal tidak ditemukan</strong>
                    <span>Coba ubah kata pencarian atau filter yang dipilih.</span>
                </div>
            </section>
        </div>
    </main>

    <script>
        (() => {
            const search = document.getElementById('scheduleSearch');
            const status = document.getElementById('statusFilter');
            const dayTabs = [...document.querySelectorAll('.day-tab')];
            const rows = [...document.querySelectorAll('.schedule-row')];
            const count = document.getElementById('visibleCount');
            const empty = document.getElementById('filterEmpty');
            let activeDay = document.querySelector('.day-tab.is-active')?.dataset.day || dayTabs[0]?.dataset.day || '';

            const applyFilters = () => {
                const query = search.value.trim().toLowerCase();
                let visible = 0;
                rows.forEach(row => {
                    const matches = (!query || row.dataset.search.includes(query))
                        && (!activeDay || row.dataset.day === activeDay)
                        && (!status.value || row.dataset.status === status.value);
                    row.hidden = !matches;
                    if (matches) visible++;
                });
                count.textContent = visible;
                empty.hidden = visible !== 0 || rows.length === 0;
            };

            [search, status].forEach(input => input.addEventListener('input', applyFilters));
            dayTabs.forEach(tab => tab.addEventListener('click', () => {
                activeDay = tab.dataset.day;
                dayTabs.forEach(item => {
                    const active = item === tab;
                    item.classList.toggle('is-active', active);
                    item.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
                applyFilters();
            }));
            applyFilters();
        })();
    </script>
</body>
</html>
