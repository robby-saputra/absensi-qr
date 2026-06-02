<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>{{ $mode === 'edit' ? 'Edit' : 'Tambah' }} Kalender Sekolah</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-admin.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        <div class="welcome">
            <div>
                <h2>{{ $mode === 'edit' ? 'Edit' : 'Tambah' }} Kalender Sekolah</h2>
                <p>Isi rentang tanggal libur, kegiatan, atau ujian sekolah.</p>
            </div>
            <a href="/dashboard/admin/kalender-sekolah" class="btn">Kembali</a>
        </div>

        <div class="panel">
            <form method="POST"
                action="{{ $mode === 'edit' ? '/dashboard/admin/kalender-sekolah/update/' . $kalender->id : '/dashboard/admin/kalender-sekolah/store' }}"
                class="admin-form">
                @csrf

                <label>
                    Tahun Ajaran
                    <select name="tahun_ajaran_id">
                        <option value="">Umum/Semua Tahun Ajaran</option>
                        @foreach ($tahunAjaran as $ta)
                            <option value="{{ $ta->id }}"
                                {{ old('tahun_ajaran_id', $kalender->tahun_ajaran_id ?? '') == $ta->id ? 'selected' : '' }}>
                                {{ $ta->nama }} - {{ ucfirst($ta->semester) }} {{ $ta->aktif ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Judul
                    <input type="text" name="judul" value="{{ old('judul', $kalender->judul ?? '') }}" required>
                </label>

                <label>
                    Jenis
                    <select name="jenis" required>
                        @foreach (['libur' => 'Libur', 'kegiatan' => 'Kegiatan', 'ujian' => 'Ujian'] as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('jenis', $kalender->jenis ?? 'libur') === $value ? 'selected' : '' }}>
                                {{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="check-row">
                    <input type="checkbox" name="berulang" value="1"
                        {{ old('berulang', $kalender->berulang ?? false) ? 'checked' : '' }}>
                    Libur/kegiatan berulang mingguan
                </label>

                <label>
                    Hari Berulang
                    <select name="hari_berulang">
                        <option value="">Tidak Berulang</option>
                        @foreach (['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'] as $hari)
                            <option value="{{ $hari }}"
                                {{ old('hari_berulang', $kalender->hari_berulang ?? '') === $hari ? 'selected' : '' }}>
                                {{ ucfirst($hari) }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Provinsi
                    <select name="provinsi">
                        <option value="">Umum</option>
                        @foreach ($provinsiList as $prov)
                            <option value="{{ $prov }}"
                                {{ old('provinsi', $kalender->provinsi ?? ($defaultProvinsi ?? 'Banten')) === $prov ? 'selected' : '' }}>
                                {{ $prov }} {{ $prov === 'Banten' ? '(Lokasi Anda)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Tanggal Mulai
                    <input type="date" name="tanggal_mulai"
                        value="{{ old('tanggal_mulai', $kalender->tanggal_mulai ?? '') }}" required>
                </label>

                <label>
                    Tanggal Selesai
                    <input type="date" name="tanggal_selesai"
                        value="{{ old('tanggal_selesai', $kalender->tanggal_selesai ?? '') }}" required>
                </label>

                <label>
                    Keterangan
                    <textarea name="keterangan" rows="4">{{ old('keterangan', $kalender->keterangan ?? '') }}</textarea>
                </label>

                <button type="submit" class="btn">{{ $mode === 'edit' ? 'Update' : 'Simpan' }}</button>
            </form>
        </div>
    </main>
</body>

</html>
