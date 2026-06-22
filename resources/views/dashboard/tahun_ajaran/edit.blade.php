<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tahun Ajaran</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-admin.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        <div class="welcome">
            <div>
                <h2>Edit Tahun Ajaran</h2>
                <p>Perbarui periode semester yang digunakan sistem.</p>
            </div>
            <a href="/dashboard/admin/tahun-ajaran" class="btn">Kembali</a>
        </div>

        <div class="panel">
            <form method="POST" action="/dashboard/admin/tahun-ajaran/update/{{ $tahun->id }}" class="admin-form">
                @csrf

                <label>
                    Nama Tahun Ajaran
                    <input type="text" name="nama" value="{{ old('nama', $tahun->nama) }}" required>
                </label>

                <label>
                    Semester
                    <select name="semester" required>
                        <option value="ganjil" {{ old('semester', $tahun->semester) === 'ganjil' ? 'selected' : '' }}>
                            Ganjil</option>
                        <option value="genap" {{ old('semester', $tahun->semester) === 'genap' ? 'selected' : '' }}>
                            Genap</option>
                    </select>
                </label>

                <label>
                    Tanggal Mulai
                    <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai', $tahun->tanggal_mulai) }}"
                        required>
                </label>

                <label>
                    Tanggal Selesai
                    <input type="date" name="tanggal_selesai"
                        value="{{ old('tanggal_selesai', $tahun->tanggal_selesai) }}" required>
                </label>

                <label class="check-row">
                    <input type="checkbox" name="aktif" value="1"
                        {{ old('aktif', $tahun->aktif) ? 'checked' : '' }}>
                    Jadikan tahun ajaran aktif
                </label>

                <button type="submit" class="btn">Update</button>
            </form>
        </div>
    </main>
</body>

</html>
