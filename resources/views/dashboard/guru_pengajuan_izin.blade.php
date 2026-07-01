{{-- File ini menampilkan daftar pengajuan izin yang dapat dilihat guru untuk memantau status izin siswa. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Izin Siswa</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-guru-izin.css') }}">
</head>
<body>
    @include('layouts.sidebar_guru')
    <main id="content" class="content">
        @include('layouts.alerts')
        <section class="permit-hero">
            <div><span>Monitoring Siswa</span><h1>Pengajuan Izin &amp; Sakit</h1><p>Pengajuan siswa pada kelas dan sesi JP yang menjadi tanggung jawab Anda.</p></div>
            <form method="GET"><label>Tanggal sesi<input type="date" name="tanggal" value="{{ $tanggal }}"></label><button>Tampilkan</button></form>
        </section>

        @php
            $jadwalPerKelas = collect($jadwalIzin)->groupBy('kelas_id');
            $pengajuanPerKelas = collect($pengajuan)->groupBy('kelas_id');
        @endphp
        <section class="permit-summary">
            <div><span>Total Pengajuan</span><strong>{{ $pengajuan->count() }}</strong></div>
            <div><span>Izin</span><strong>{{ $pengajuan->where('jenis','izin')->count() }}</strong></div>
            <div><span>Sakit</span><strong>{{ $pengajuan->where('jenis','sakit')->count() }}</strong></div>
            <div><span>Sesi JP Terdampak</span><strong>{{ $jadwalIzin->count() }}</strong></div>
        </section>

        @forelse($pengajuanPerKelas as $kelasId => $items)
            @php $sesiKelas = $jadwalPerKelas->get($kelasId, collect()); @endphp
            <section class="permit-class-card">
                <header><div><span>Kelas</span><h2>{{ $items->first()->nama_kelas ?? 'Tanpa Kelas' }}</h2></div><b>{{ $items->count() }} pengajuan</b></header>
                <div class="permit-jp-list">
                    @forelse($sesiKelas as $sesi)
                        <div><strong>{{ labelJadwalJp($sesi, false) }}</strong><span>{{ $sesi->nama_mapel }} · {{ substr($sesi->jam_mulai,0,5) }}–{{ substr($sesi->jam_selesai,0,5) }}</span><small>{{ $sesi->role_mengajar === 'guru_pengganti' ? 'Guru Pengganti' : 'Guru Utama' }}</small></div>
                    @empty <span class="no-session">Tidak ada sesi JP pada tanggal ini.</span> @endforelse
                </div>
                <div class="table-wrap"><table><thead><tr><th>Siswa</th><th>Periode</th><th>Jenis</th><th>Status</th><th>Reviewer</th><th>Catatan</th></tr></thead><tbody>
                    @foreach($items as $p)<tr><td><strong>{{ $p->nama_siswa }}</strong><small>{{ $p->nis ?? '-' }}</small></td><td>{{ \Carbon\Carbon::parse($p->tanggal_mulai)->format('d/m/Y') }}–{{ \Carbon\Carbon::parse($p->tanggal_selesai)->format('d/m/Y') }}</td><td><span class="pill {{ strtolower($p->jenis) }}">{{ ucfirst($p->jenis) }}</span></td><td><span class="pill status">{{ ucfirst($p->status) }}</span></td><td>{{ $p->reviewer ?? 'Belum direview' }}</td><td>{{ $p->catatan_review ?? '-' }}</td></tr>@endforeach
                </tbody></table></div>
            </section>
        @empty
            <div class="empty-state"><strong>Belum ada pengajuan</strong><span>Tidak ada izin atau sakit pada kelas yang Anda ajar di tanggal ini.</span></div>
        @endforelse
    </main>
    <script src="{{ asset('js/app-ui.js') }}"></script>
</body></html>
