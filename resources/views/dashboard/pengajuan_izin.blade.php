<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Pengajuan Izin/Sakit</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')
    <main id="content" class="content">
        @include('layouts.alerts')

        <div class="rekap-head">
            <div>
                <h1>Pengajuan Izin/Sakit</h1>
                <p>Verifikasi pengajuan siswa. Jika disetujui, absensi harian otomatis terisi izin/sakit.</p>
            </div>
            <div><a class="btn back" href="/dashboard/admin">Kembali</a><a class="btn" target="_blank"
                    href="/dashboard/admin/pdf/pengajuan-izin">PDF Resmi</a></div>
        </div>
        <table>
            <tr>
                <th>Siswa</th>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Alasan/Bukti</th>
                <th>Status</th>
                <th>Review</th>
            </tr>
            @forelse($pengajuan as $p)
                <tr>
                    <td>{{ $p->nama_siswa }}<br><small>{{ $p->nama_kelas ?? '-' }}</small></td>
                    <td>{{ $p->tanggal_mulai }} s/d {{ $p->tanggal_selesai }}</td>
                    <td><span class="status-pill">{{ $p->jenis }}</span></td>
                    <td>{{ $p->alasan ?? '-' }}<br>
                        @if ($p->bukti_path)
                            <a class="btn back" target="_blank" href="{{ asset('storage/' . $p->bukti_path) }}">Bukti</a>
                        @endif
                    </td>
                    <td>{{ $p->status }}<br><small>{{ $p->reviewer ?? '-' }}</small></td>
                    <td>
                        <form method="POST" action="/dashboard/admin/pengajuan-izin/{{ $p->id }}/review"
                            class="rekap-filter" style="box-shadow:none;padding:0;margin:0">
                            @csrf
                            <select name="status">
                                <option value="disetujui">Setujui</option>
                                <option value="ditolak">Tolak</option>
                            </select>
                            <input type="text" name="catatan_review" placeholder="Catatan">
                            <button class="btn" type="submit">Simpan</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty-row">Belum ada pengajuan.</td>
                </tr>
            @endforelse
        </table>
    </main>
</body>

</html>
