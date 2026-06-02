<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>{{ $mode === 'edit' ? 'Edit' : 'Tambah' }} Pengumuman</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')
    <main id="content" class="content">
        <div class="rekap-head">
            <div>
                <h1>{{ $mode === 'edit' ? 'Edit' : 'Tambah' }} Pengumuman</h1>
                <p>Pengumuman aktif akan tampil di dashboard pengumuman guru/piket/wali.</p>
            </div><a class="btn back" href="/dashboard/admin/pengumuman">Kembali</a>
        </div>
        <form class="rekap-filter" method="POST"
            action="{{ $mode === 'edit' ? '/dashboard/admin/pengumuman/update/' . $item->id : '/dashboard/admin/pengumuman/store' }}">
            @csrf
            <input type="text" name="judul" placeholder="Judul" value="{{ old('judul', $item->judul ?? '') }}"
                required>
            <select name="target_role">
                @foreach (['semua' => 'Semua', 'guru' => 'Guru', 'wali' => 'Wali Kelas', 'piket' => 'Guru Piket'] as $k => $v)
                    <option value="{{ $k }}"
                        {{ old('target_role', $item->target_role ?? 'semua') === $k ? 'selected' : '' }}>
                        {{ $v }}</option>
                @endforeach
            </select>
            <select name="kategori">
                @foreach (['info' => 'Info', 'libur' => 'Libur', 'ujian' => 'Ujian', 'jadwal' => 'Jadwal', 'piket' => 'Piket'] as $k => $v)
                    <option value="{{ $k }}"
                        {{ old('kategori', $item->kategori ?? 'info') === $k ? 'selected' : '' }}>{{ $v }}
                    </option>
                @endforeach
            </select>
            <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai', $item->tanggal_mulai ?? '') }}">
            <input type="date" name="tanggal_selesai"
                value="{{ old('tanggal_selesai', $item->tanggal_selesai ?? '') }}">
            <textarea name="isi" placeholder="Isi pengumuman" rows="6" required style="width:100%">{{ old('isi', $item->isi ?? '') }}</textarea>
            <label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="aktif" value="1"
                    {{ old('aktif', $item->aktif ?? 1) ? 'checked' : '' }}> Aktif</label>
            <button class="btn" type="submit">Simpan</button>
        </form>
    </main>
</body>

</html>
