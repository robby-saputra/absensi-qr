<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Pesan Internal</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>

<body>
    @include(($user->role ?? '') === 'piket' ? 'layouts.sidebar_piket' : 'layouts.sidebar_guru')
    <main id="content" class="content">
        <div class="rekap-head">
            <div>
                <h1>Pesan Internal</h1>
                <p>Kirim catatan ringan antar guru/piket/wali terkait siswa tertentu.</p>
            </div>
            <a class="btn back"
                href="{{ ($user->role ?? '') === 'piket' ? '/dashboard/piket' : '/dashboard/guru' }}">Kembali</a>
        </div>
        <form method="POST" class="rekap-filter">
            @csrf
            <label>Penerima
                <select name="receiver_id" required>
                    @foreach ($guru as $g)
                        <option value="{{ $g->id }}">{{ $g->nama }}</option>
                    @endforeach
                </select>
            </label>
            <label>Siswa Terkait
                <select name="siswa_id">
                    <option value="">Tidak ada</option>
                    @foreach ($siswa as $s)
                        <option value="{{ $s->id }}">{{ $s->nama }}</option>
                    @endforeach
                </select>
            </label>
            <label>Judul <input name="judul" placeholder="Contoh: Catatan keterlambatan"></label>
            <label>Pesan
                <textarea name="pesan" rows="3" required placeholder="Tulis pesan singkat"></textarea>
            </label>
            <button class="btn">Kirim Pesan</button>
        </form>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Waktu</th>
                    <th>Pengirim</th>
                    <th>Siswa</th>
                    <th>Judul</th>
                    <th>Pesan</th>
                </tr>
                @forelse($inbox as $m)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($m->created_at)->format('d-m-Y H:i') }}</td>
                        <td>{{ $m->pengirim }}</td>
                        <td>{{ $m->nama_siswa ?? '-' }}</td>
                        <td>{{ $m->judul ?? '-' }}</td>
                        <td>{{ $m->pesan }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Belum ada pesan.</td>
                    </tr>
                @endforelse
            </table>
        </div>
    </main>
</body>

</html>
