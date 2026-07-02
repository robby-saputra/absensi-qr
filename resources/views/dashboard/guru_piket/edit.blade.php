{{-- File ini menampilkan form edit guru piket agar data petugas piket dapat diperbarui sesuai kebutuhan sekolah. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Guru Piket</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-guru_piket-edit.css') }}?v={{ filemtime(public_path('css/pages/dashboard-guru_piket-edit.css')) }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        <div class="piket-edit-page">
            <section class="edit-hero">
                <div>
                    <span>Manajemen Petugas Sekolah</span>
                    <h1>Edit Guru Piket</h1>
                    <p>Perbarui jadwal, petugas, status, dan masa aktif guru piket dengan data yang tepat.</p>
                </div>
                <a href="/dashboard/admin/guru-piket" class="hero-back">Kembali</a>
            </section>

            @if (session('error'))
                <div class="edit-alert">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="edit-alert">{{ $errors->first() }}</div>
            @endif

            <div class="edit-layout">
                <aside class="overview-card">
                    @php
                        $namaGuruSaatIni = optional($guru->firstWhere('id', $guruPiket->guru_id))->nama ?? 'Guru piket';
                        $inisial = collect(explode(' ', trim($namaGuruSaatIni)))
                            ->filter()
                            ->take(2)
                            ->map(fn ($kata) => strtoupper(substr($kata, 0, 1)))
                            ->implode('');
                    @endphp
                    <div class="teacher-avatar">{{ $inisial ?: 'GP' }}</div>
                    <span class="eyebrow">Data Saat Ini</span>
                    <h2>{{ $namaGuruSaatIni }}</h2>
                    <div class="overview-list">
                        <div><span>Hari</span><strong>{{ ucfirst($guruPiket->hari) }}</strong></div>
                        <div><span>Jam Piket</span><strong>{{ substr($guruPiket->jam_mulai, 0, 5) }} - {{ substr($guruPiket->jam_selesai, 0, 5) }}</strong></div>
                        <div><span>Status</span><strong>{{ $guruPiket->status }}</strong></div>
                        <div><span>Aktif</span><strong>{{ $guruPiket->aktif ? 'Ya' : 'Tidak' }}</strong></div>
                    </div>
                    <p>Gunakan form di samping untuk memperbarui jadwal atau status petugas piket ini.</p>
                </aside>

                <section class="edit-card">
                    <header>
                        <div>
                            <span>Form Perubahan</span>
                            <h2>Detail Guru Piket</h2>
                            <p>Pastikan jadwal dan status sesuai dengan kondisi terbaru.</p>
                        </div>
                    </header>

                    <form action="/dashboard/admin/guru-piket/update/{{ $guruPiket->id }}" method="POST">
                        @csrf

                        <label class="field full">
                            <span>Tahun Ajaran</span>
                            <select name="tahun_ajaran_id">
                                @foreach ($tahunAjaran as $ta)
                                    <option value="{{ $ta->id }}"
                                        {{ old('tahun_ajaran_id', $guruPiket->tahun_ajaran_id ?? ($tahunAjaranAktif->id ?? '')) == $ta->id ? 'selected' : '' }}>
                                        {{ $ta->nama }} - {{ ucfirst($ta->semester) }} {{ $ta->aktif ? '(Aktif)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="field full">
                            <span>Guru Piket <b>*</b></span>
                            <select name="guru_id" required>
                                @foreach ($guru as $g)
                                    <option value="{{ $g->id }}"
                                        {{ old('guru_id', $guruPiket->guru_id) == $g->id ? 'selected' : '' }}>
                                        {{ $g->nama }} ({{ $g->username }})
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="field">
                            <span>Hari <b>*</b></span>
                            <select name="hari" required>
                                @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h)
                                    <option value="{{ $h }}"
                                        {{ old('hari', ucfirst($guruPiket->hari)) == $h ? 'selected' : '' }}>
                                        {{ $h }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="field">
                            <span>Status <b>*</b></span>
                            <select name="status" required>
                                @foreach (['Akan Bertugas', 'Sedang Bertugas', 'Izin', 'Sakit', 'Selesai'] as $status)
                                    <option value="{{ $status }}"
                                        {{ old('status', $guruPiket->status) == $status ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="field">
                            <span>Jam Mulai <b>*</b></span>
                            <input type="time" name="jam_mulai"
                                value="{{ old('jam_mulai', substr($guruPiket->jam_mulai, 0, 5)) }}" required>
                        </label>

                        <label class="field">
                            <span>Jam Selesai <b>*</b></span>
                            <input type="time" name="jam_selesai"
                                value="{{ old('jam_selesai', substr($guruPiket->jam_selesai, 0, 5)) }}" required>
                        </label>

                        <label class="active-toggle">
                            <input type="checkbox" name="aktif" value="1"
                                {{ old('aktif', $guruPiket->aktif) ? 'checked' : '' }}>
                            <span></span>
                            <div>
                                <strong>Jadwal Aktif</strong>
                                <small>Aktifkan jika guru piket masih digunakan dalam jadwal berjalan.</small>
                            </div>
                        </label>

                        <footer class="form-actions">
                            <a href="/dashboard/admin/guru-piket" class="btn cancel">Batal</a>
                            <button type="submit" class="btn save">Update Guru Piket</button>
                        </footer>
                    </form>
                </section>
            </div>
        </div>

    </main>
</body>

</html>
