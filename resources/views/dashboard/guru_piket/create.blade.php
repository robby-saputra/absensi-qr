<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Tambah Guru Piket</title>
    <link rel="stylesheet"
        href="{{ asset('css/pages/dashboard-guru_piket-create.css') }}?v={{ filemtime(public_path('css/pages/dashboard-guru_piket-create.css')) }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        <section class="piket-form-page">
            <div class="form-hero">
                <div>
                    <span class="eyebrow">Buat Tim Baru</span>
                    <h1>Tambah Guru Piket</h1>
                    <p>Pilih minimal 5 guru, atur jadwal, lalu sistem akan menampilkannya sebagai satu tim piket.</p>
                </div>
                <a href="/dashboard/admin/guru-piket" class="btn btn-ghost">Kembali</a>
            </div>

            @if (session('error'))
                <div class="alert">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert">{{ $errors->first() }}</div>
            @endif

            <form action="/dashboard/admin/guru-piket/store" method="POST" class="team-form">
                @csrf

                <div class="form-grid">
                    <div class="panel schedule-panel">
                        <div class="panel-title">
                            <span>01</span>
                            <div>
                                <h2>Jadwal Tim</h2>
                                <p>Tentukan tahun ajaran, hari, dan jam bertugas.</p>
                            </div>
                        </div>

                        <div class="field">
                            <label for="tahun_ajaran_id">Tahun Ajaran</label>
                            <select id="tahun_ajaran_id" name="tahun_ajaran_id">
                                @foreach ($tahunAjaran as $ta)
                                    <option value="{{ $ta->id }}"
                                        {{ old('tahun_ajaran_id', $tahunAjaranAktif->id ?? '') == $ta->id ? 'selected' : '' }}>
                                        {{ $ta->nama }} - {{ ucfirst($ta->semester) }}
                                        {{ $ta->aktif ? '(Aktif)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label for="hari">Hari</label>
                            <select id="hari" name="hari" required>
                                @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h)
                                    <option value="{{ $h }}" {{ old('hari') == $h ? 'selected' : '' }}>
                                        {{ $h }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="time-grid">
                            <div class="field">
                                <label for="jam_mulai">Jam Mulai</label>
                                <input id="jam_mulai" type="time" name="jam_mulai" value="{{ old('jam_mulai') }}"
                                    required>
                            </div>

                            <div class="field">
                                <label for="jam_selesai">Jam Selesai</label>
                                <input id="jam_selesai" type="time" name="jam_selesai"
                                    value="{{ old('jam_selesai') }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="panel replacement-panel">
                        <div class="panel-title">
                            <span>02</span>
                            <div>
                                <h2>Guru Pengganti</h2>
                                <p>Opsional, tampil di kartu tim kalau ada guru yang menggantikan.</p>
                            </div>
                        </div>

                        <div class="field">
                            <label for="guru_pengganti_id">Guru Pengganti 1</label>
                            <select id="guru_pengganti_id" name="guru_pengganti_id">
                                <option value="">Tidak Ada</option>
                                @foreach ($guru as $g)
                                    <option value="{{ $g->id }}"
                                        {{ old('guru_pengganti_id') == $g->id ? 'selected' : '' }}>
                                        {{ $g->nama }} ({{ $g->username }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label for="guru_pengganti2_id">Guru Pengganti 2</label>
                            <select id="guru_pengganti2_id" name="guru_pengganti2_id">
                                <option value="">Tidak Ada</option>
                                @foreach ($guru as $g)
                                    <option value="{{ $g->id }}"
                                        {{ old('guru_pengganti2_id') == $g->id ? 'selected' : '' }}>
                                        {{ $g->nama }} ({{ $g->username }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="panel teacher-panel">
                    <div class="panel-title teacher-title">
                        <span>03</span>
                        <div>
                            <h2>Anggota Tim Piket</h2>
                            <p>Pilih minimal 5 guru untuk membentuk satu tim.</p>
                        </div>
                        <strong id="selectedCounter">0 dipilih</strong>
                    </div>

                    <div class="teacher-grid">
                        @foreach ($guru as $g)
                            @php
                                $checked = in_array($g->id, old('guru_id', []));
                                $inisial = collect(explode(' ', trim($g->nama)))
                                    ->filter()
                                    ->take(2)
                                    ->map(fn($nama) => strtoupper(substr($nama, 0, 1)))
                                    ->implode('');
                            @endphp

                            <label class="teacher-card">
                                <input type="checkbox" name="guru_id[]" value="{{ $g->id }}"
                                    {{ $checked ? 'checked' : '' }}>
                                <span class="teacher-avatar">{{ $inisial ?: 'GP' }}</span>
                                <span class="teacher-info">
                                    <strong>{{ $g->nama }}</strong>
                                    <small>{{ $g->username }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="submit-bar">
                    <div>
                        <strong>Siap simpan tim?</strong>
                        <span>Data dengan hari dan jam yang sama akan muncul sebagai satu kartu tim di halaman guru
                            piket.</span>
                    </div>
                    <button type="submit" class="btn btn-save">Simpan Guru Piket</button>
                </div>
            </form>
        </section>

        <script>
            const checks = document.querySelectorAll('input[name="guru_id[]"]');
            const counter = document.getElementById('selectedCounter');

            function updateCounter() {
                const total = [...checks].filter((item) => item.checked).length;
                counter.textContent = `${total} dipilih`;
                counter.classList.toggle('ready', total >= 5);
            }

            checks.forEach((item) => item.addEventListener('change', updateCounter));
            updateCounter();
        </script>
    </main>
</body>

</html>
