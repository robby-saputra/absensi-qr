{{-- Monitoring harian verifikasi guru piket dan guru mata pelajaran. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Verifikasi Guru</title>
    <style>
        .monitor-page{padding:24px;display:grid;gap:18px}.monitor-hero,.monitor-panel,.filter-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 10px 30px rgba(15,23,42,.06)}.monitor-hero{padding:24px;display:flex;justify-content:space-between;gap:16px;align-items:flex-start}.monitor-hero h1{margin:6px 0;font-size:28px;color:#0f172a}.monitor-hero p{margin:0;color:#64748b}.eyebrow{font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:#2563eb;font-weight:700}.hero-actions,.tabs,.row-actions,.filter-actions{display:flex;gap:10px;flex-wrap:wrap}.btn,.tab-link,.mini-btn{border:0;border-radius:8px;padding:10px 14px;text-decoration:none;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px}.btn-primary{background:#2563eb;color:#fff}.btn-soft{background:#e0f2fe;color:#075985}.btn-ghost{background:#f8fafc;color:#334155;border:1px solid #e2e8f0}.filter-card{padding:16px;display:flex;justify-content:space-between;gap:16px;align-items:end}.filter-card label{display:grid;gap:6px;color:#475569;font-weight:700}.filter-card input{border:1px solid #cbd5e1;border-radius:8px;padding:10px 12px}.tabs{padding:16px 16px 0}.tab-link{background:#f8fafc;color:#475569;border:1px solid #e2e8f0}.tab-link.active{background:#0f172a;color:#fff}.table-wrap{overflow:auto;padding:16px}.monitor-table{width:100%;border-collapse:collapse;min-width:980px}.monitor-table th,.monitor-table td{padding:12px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}.monitor-table th{font-size:12px;text-transform:uppercase;color:#64748b;background:#f8fafc}.status-badge{display:inline-flex;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:800;background:#f1f5f9;color:#334155}.status-badge.ok{background:#dcfce7;color:#166534}.status-badge.warn{background:#fef3c7;color:#92400e}.status-badge.danger{background:#fee2e2;color:#991b1b}.chain{display:grid;gap:4px;font-size:12px;color:#475569}.mini-btn{font-size:12px;padding:8px 10px}.mini-btn.view{background:#f1f5f9;color:#334155}.mini-btn.cancel{background:#fee2e2;color:#991b1b}.alert{padding:12px 14px;border-radius:8px;font-weight:700}.alert.success{background:#dcfce7;color:#166534}.alert.error{background:#fee2e2;color:#991b1b}.empty-state{padding:32px;text-align:center;color:#64748b}.modal-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;padding:20px;z-index:50}.modal-backdrop.active{display:flex}.modal-card{background:#fff;border-radius:8px;max-width:640px;width:100%;max-height:90vh;overflow:auto;padding:22px;box-shadow:0 24px 80px rgba(15,23,42,.25)}.modal-card h2{margin:0 0 12px}.detail-grid{display:grid;grid-template-columns:180px 1fr;gap:8px 14px;font-size:14px}.detail-grid dt{font-weight:800;color:#475569}.detail-grid dd{margin:0;color:#0f172a}.warning-box{margin:14px 0;padding:12px;border-radius:8px;background:#fff7ed;color:#9a3412;border:1px solid #fed7aa}.modal-card textarea{width:100%;min-height:110px;border:1px solid #cbd5e1;border-radius:8px;padding:10px;resize:vertical}.modal-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:14px}.inline-detail{display:none;margin-top:8px;padding:10px;border-radius:8px;background:#f8fafc;color:#475569}.inline-detail.active{display:block}@media(max-width:760px){.monitor-page{padding:14px}.monitor-hero,.filter-card{display:grid}.detail-grid{grid-template-columns:1fr}.monitor-table{min-width:760px}}
    </style>
</head>
<body>
@include('layouts.sidebar_admin')

<main id="content" class="content">
    <div class="monitor-page">
        <section class="monitor-hero">
            <div>
                <span class="eyebrow">Monitoring Admin</span>
                <h1>Verifikasi Kehadiran Guru</h1>
                <p>Batalkan verifikasi harian agar guru utama dapat memilih ulang status kehadiran tanpa mengubah jadwal rutin.</p>
            </div>
            <div class="hero-actions">
                <a class="btn btn-ghost" href="/dashboard/admin"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
                <a class="btn btn-soft" href="/dashboard/admin/guru-piket?tanggal={{ $tanggal }}"><i class="fa-solid fa-user-shield"></i> Guru Piket</a>
                <a class="btn btn-soft" href="/dashboard/admin/jadwal"><i class="fa-solid fa-calendar-days"></i> Jadwal Mapel</a>
            </div>
        </section>

        @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert error">{{ session('error') }}</div>@endif
        @if($isAfterCutoff)<div class="alert error">Tanggal ini dilihat setelah cutoff konfirmasi. Admin tetap dapat membatalkan sebagai override dan tindakan akan tercatat di audit log.</div>@endif

        <form method="GET" class="filter-card">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <label>
                <span>Tanggal tugas</span>
                <input type="date" name="tanggal" value="{{ $tanggal }}">
            </label>
            <div class="filter-actions">
                <a class="btn btn-ghost" href="/dashboard/admin/monitoring-verifikasi-guru">Hari Ini</a>
                <button class="btn btn-primary"><i class="fa-solid fa-filter"></i> Terapkan</button>
            </div>
        </form>

        <section class="monitor-panel">
            <nav class="tabs">
                <a class="tab-link {{ $tab === 'piket' ? 'active' : '' }}" href="?tanggal={{ $tanggal }}&tab=piket">Guru Piket</a>
                <a class="tab-link {{ $tab === 'mapel' ? 'active' : '' }}" href="?tanggal={{ $tanggal }}&tab=mapel">Guru Mata Pelajaran</a>
            </nav>

            @if($tab === 'mapel')
                <div class="table-wrap">
                    <table class="monitor-table">
                        <thead><tr><th>Guru Utama</th><th>Tanggal</th><th>Kelas & Mapel</th><th>JP/Jam</th><th>Status</th><th>Pengganti</th><th>Aksi</th></tr></thead>
                        <tbody>
                        @forelse($mapelRows as $row)
                            @php
                                $statusClass = $row->sudah_verifikasi ? (in_array($row->status_harian, ['izin','sakit','inval']) ? 'danger' : 'ok') : 'warn';
                                $modalId = 'cancel-mapel-'.$row->attendance_id;
                            @endphp
                            <tr>
                                <td><strong>{{ $row->nama_guru }}</strong></td>
                                <td>{{ \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('l, d F Y') }}</td>
                                <td><strong>{{ $row->nama_kelas }}</strong><br>{{ $row->nama_mapel }}</td>
                                <td>{{ labelJadwalJp($row, false) }}<br>{{ substr($row->jam_mulai,0,5) }} - {{ substr($row->jam_selesai,0,5) }}</td>
                                <td><span class="status-badge {{ $statusClass }}">{{ $row->status_label }}</span><br><small>{{ $row->status_dipilih_at ? \Carbon\Carbon::parse($row->status_dipilih_at)->format('d/m/Y H:i') : '-' }}</small></td>
                                <td>
                                    <div class="chain">
                                        <span>Aktif: {{ $row->active_teacher_name ?: '-' }}</span>
                                        @forelse($row->replacement_chain as $r)
                                            <span>#{{ $r->urutan_penggantian }} {{ $r->nama_pengganti }} - {{ ucfirst(str_replace('_',' ', $r->status_penugasan)) }}</span>
                                        @empty
                                            <span>Belum ada pengganti</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <button type="button" class="mini-btn view" data-toggle-detail="detail-mapel-{{ $row->id }}">Detail</button>
                                        @if($row->sudah_verifikasi && $row->attendance_id)
                                            <button type="button" class="mini-btn cancel" data-open-modal="{{ $modalId }}">Batalkan Verifikasi</button>
                                        @endif
                                    </div>
                                    <div id="detail-mapel-{{ $row->id }}" class="inline-detail">
                                        Pengganti lanjutan: {{ $row->continuations->pluck('nama_pengganti')->implode(', ') ?: '-' }}<br>
                                        Absensi siswa: {{ $row->has_student_attendance ? 'Sudah ada' : 'Belum ada' }}
                                    </div>
                                    @if($row->sudah_verifikasi && $row->attendance_id)
                                        @include('dashboard.admin_teacher_verifications.partials.cancel_modal', [
                                            'modalId' => $modalId,
                                            'action' => route('admin.teacher-verifications.subject.cancel', $row->attendance_id),
                                            'guru' => $row->nama_guru,
                                            'jenis' => 'Guru Mata Pelajaran',
                                            'tanggalLabel' => \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('l, d F Y'),
                                            'extra' => $row->nama_kelas.' - '.$row->nama_mapel.' - '.labelJadwalJp($row, false),
                                            'statusLama' => $row->status_label,
                                            'waktuLama' => $row->status_dipilih_at ? \Carbon\Carbon::parse($row->status_dipilih_at)->format('d/m/Y H:i') : '-',
                                            'penggantiAktif' => optional($row->active_replacement)->nama_pengganti ?: '-',
                                            'penggantiLanjutan' => $row->continuations->pluck('nama_pengganti')->implode(', ') ?: '-',
                                            'hasStudentAttendance' => $row->has_student_attendance,
                                            'isAfterCutoff' => $isAfterCutoff,
                                        ])
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><div class="empty-state">Tidak ada jadwal mapel pada tanggal ini.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="table-wrap">
                    <table class="monitor-table">
                        <thead><tr><th>Guru Utama</th><th>Tanggal</th><th>Hari</th><th>Jam Piket</th><th>Status</th><th>Pengganti</th><th>Petugas Aktif</th><th>Aksi</th></tr></thead>
                        <tbody>
                        @forelse($piketRows as $row)
                            @php
                                $statusClass = $row->sudah_verifikasi ? (in_array($row->status_harian, ['izin','sakit']) ? 'danger' : 'ok') : 'warn';
                                $modalId = 'cancel-piket-'.$row->attendance_id;
                            @endphp
                            <tr>
                                <td><strong>{{ $row->nama_guru }}</strong></td>
                                <td>{{ \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('l, d F Y') }}</td>
                                <td>{{ ucfirst($row->hari) }}</td>
                                <td>{{ substr($row->jam_mulai,0,5) }} - {{ substr($row->jam_selesai,0,5) }}</td>
                                <td><span class="status-badge {{ $statusClass }}">{{ $row->status_label }}</span><br><small>{{ $row->waktu_konfirmasi ? \Carbon\Carbon::parse($row->waktu_konfirmasi)->format('d/m/Y H:i') : '-' }}</small></td>
                                <td>
                                    <div class="chain">
                                        @forelse($row->replacement_chain as $r)
                                            <span>#{{ $r->urutan_penggantian }} {{ $r->nama_pengganti }} - {{ ucfirst(str_replace('_',' ', $r->status_penugasan)) }}</span>
                                        @empty
                                            <span>Belum ada pengganti</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>{{ $row->active_officer_label }}</td>
                                <td>
                                    <div class="row-actions">
                                        <button type="button" class="mini-btn view" data-toggle-detail="detail-piket-{{ $row->id }}">Detail</button>
                                        @if($row->sudah_verifikasi && $row->attendance_id)
                                            <button type="button" class="mini-btn cancel" data-open-modal="{{ $modalId }}">Batalkan Verifikasi</button>
                                        @endif
                                    </div>
                                    <div id="detail-piket-{{ $row->id }}" class="inline-detail">
                                        Pengganti aktif: {{ optional($row->active_replacement)->nama_pengganti ?: '-' }}<br>
                                        Pengganti lanjutan: {{ $row->continuations->pluck('nama_pengganti')->implode(', ') ?: '-' }}<br>
                                        Absensi siswa: {{ $row->has_student_attendance ? 'Sudah ada' : 'Belum ada' }}
                                    </div>
                                    @if($row->sudah_verifikasi && $row->attendance_id)
                                        @include('dashboard.admin_teacher_verifications.partials.cancel_modal', [
                                            'modalId' => $modalId,
                                            'action' => route('admin.teacher-verifications.duty.cancel', $row->attendance_id),
                                            'guru' => $row->nama_guru,
                                            'jenis' => 'Guru Piket',
                                            'tanggalLabel' => \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('l, d F Y'),
                                            'extra' => ucfirst($row->hari).' '.$row->jam_mulai.' - '.$row->jam_selesai,
                                            'statusLama' => $row->status_label,
                                            'waktuLama' => $row->waktu_konfirmasi ? \Carbon\Carbon::parse($row->waktu_konfirmasi)->format('d/m/Y H:i') : '-',
                                            'penggantiAktif' => optional($row->active_replacement)->nama_pengganti ?: '-',
                                            'penggantiLanjutan' => $row->continuations->pluck('nama_pengganti')->implode(', ') ?: '-',
                                            'hasStudentAttendance' => $row->has_student_attendance,
                                            'isAfterCutoff' => $isAfterCutoff,
                                        ])
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><div class="empty-state">Tidak ada jadwal guru piket pada tanggal ini.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</main>

<script>
document.querySelectorAll('[data-open-modal]').forEach(button => {
    button.addEventListener('click', () => document.getElementById(button.dataset.openModal)?.classList.add('active'));
});
document.querySelectorAll('[data-close-modal]').forEach(button => {
    button.addEventListener('click', () => button.closest('.modal-backdrop')?.classList.remove('active'));
});
document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
    backdrop.addEventListener('click', event => {
        if (event.target === backdrop) backdrop.classList.remove('active');
    });
});
document.querySelectorAll('[data-toggle-detail]').forEach(button => {
    button.addEventListener('click', () => document.getElementById(button.dataset.toggleDetail)?.classList.toggle('active'));
});
</script>
</body>
</html>
