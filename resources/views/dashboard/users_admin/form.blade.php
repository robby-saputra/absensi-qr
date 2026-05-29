<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $mode === 'edit' ? 'Edit' : 'Tambah' }} User</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
</head>
<body>
@include('layouts.sidebar_admin')

<main id="content" class="content">
    <div class="rekap-head">
        <div>
            <h1>{{ $mode === 'edit' ? 'Edit' : 'Tambah' }} User</h1>
            <p>Role admin di sistem ini dipakai untuk superadmin. Guru piket tetap memakai role guru dengan jadwal piket.</p>
        </div>
        <div>
            <a class="btn back" href="/dashboard/admin/users">Kembali</a>
        </div>
    </div>

    <form method="POST" action="{{ $mode === 'edit' ? '/dashboard/admin/users/update/'.$target->id : '/dashboard/admin/users/store' }}" class="rekap-filter">
        @csrf
        <input type="text" name="nama" value="{{ old('nama', $target->nama ?? '') }}" placeholder="Nama" required>
        <input type="text" name="username" value="{{ old('username', $target->username ?? '') }}" placeholder="Username" required>
        <input type="password" name="password" placeholder="{{ $mode === 'edit' ? 'Password baru opsional' : 'Password' }}" {{ $mode === 'create' ? 'required' : '' }}>
        <select name="role" required>
            @php($roleOptions = ($target && $target->role === 'admin' && $target->admin_level === 'superadmin') ? ['admin' => 'Admin/Superadmin', 'guru' => 'Guru', 'siswa' => 'Siswa'] : ['guru' => 'Guru', 'siswa' => 'Siswa'])
            @foreach($roleOptions as $value => $label)
                <option value="{{ $value }}" {{ old('role', $target->role ?? 'guru') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @if($target && $target->role === 'admin' && $target->admin_level === 'superadmin')
            <input type="hidden" name="admin_level" value="superadmin">
            <input type="text" value="Superadmin utama" disabled>
        @endif
        <select name="kelas_id">
            <option value="">Kelas siswa</option>
            @foreach($kelas as $k)
                <option value="{{ $k->id }}" {{ old('kelas_id', $target->kelas_id ?? '') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
            @endforeach
        </select>
        <input type="text" name="nis" value="{{ old('nis', $target->nis ?? '') }}" placeholder="NIS siswa">
        <input type="text" name="nuptk" value="{{ old('nuptk', $target->nuptk ?? '') }}" placeholder="NUPTK guru">
        <input type="text" name="nama_ortu" value="{{ old('nama_ortu', $target->nama_ortu ?? '') }}" placeholder="Nama orang tua">
        <input type="text" name="no_ortu" value="{{ old('no_ortu', $target->no_ortu ?? '') }}" placeholder="No orang tua">
        <label style="display:flex;align-items:center;gap:8px">
            <input type="checkbox" name="aktif" value="1" {{ old('aktif', $target->aktif ?? 1) ? 'checked' : '' }}>
            Aktif
        </label>
        <button class="btn" type="submit">Simpan</button>
    </form>
</main>
</body>
</html>
