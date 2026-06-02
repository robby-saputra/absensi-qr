<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Delegasi Sementara</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>

<body>
    @include(($user->role ?? '') === 'piket' ? 'layouts.sidebar_piket' : 'layouts.sidebar_guru')
    <main id="content" class="content">
        <div class="rekap-head">
            <div>
                <h1>Delegasi Sementara</h1>
                <p>Catat guru pengganti sementara saat berhalangan.</p>
            </div>
            <a class="btn back"
                href="{{ ($user->role ?? '') === 'piket' ? '/dashboard/piket' : '/dashboard/guru' }}">Kembali</a>
        </div>
        <form method="POST" class="rekap-filter">
            @csrf
            <label>Pengganti
                <select name="to_user_id" required>
                    @foreach ($guru as $g)
                        <option value="{{ $g->id }}">{{ $g->nama }}</option>
                    @endforeach
                </select>
            </label>
            <label>Konteks
                <select name="role_context" required>
                    <option value="guru_mapel">Guru Mapel</option>
                    <option value="guru_piket">Guru Piket</option>
                    <option value="wali_kelas">Wali Kelas</option>
                </select>
            </label>
            <label>Mulai <input type="date" name="tanggal_mulai" value="{{ now()->toDateString() }}"
                    required></label>
            <label>Selesai <input type="date" name="tanggal_selesai" value="{{ now()->toDateString() }}"
                    required></label>
            <label>Alasan
                <textarea name="alasan" rows="3" placeholder="Alasan delegasi"></textarea>
            </label>
            <button class="btn">Simpan Delegasi</button>
        </form>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Pengganti</th>
                    <th>Konteks</th>
                    <th>Periode</th>
                    <th>Alasan</th>
                    <th>Status</th>
                </tr>
                @forelse($delegasi as $d)
                    <tr>
                        <td>{{ $d->nama_pengganti }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $d->role_context)) }}</td>
                        <td>{{ $d->tanggal_mulai }} s/d {{ $d->tanggal_selesai }}</td>
                        <td>{{ $d->alasan ?? '-' }}</td>
                        <td><span class="status-pill">{{ $d->status }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Belum ada delegasi.</td>
                    </tr>
                @endforelse
            </table>
        </div>
    </main>
</body>

</html>
