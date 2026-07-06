<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Jadwal</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-jadwal-create.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content">

        <div class="box">
            <h2>Edit Jadwal Pelajaran</h2>

            @if (session('error'))
                <div class="info error">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="info error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="/dashboard/admin/jadwal/update/{{ $jadwal->id }}">
                @csrf

                <label>Tahun Ajaran</label>
                <select name="tahun_ajaran_id">
                    @foreach ($tahunAjaran as $ta)
                        <option value="{{ $ta->id }}"
                            {{ old('tahun_ajaran_id', $jadwal->tahun_ajaran_id ?? ($tahunAjaranAktif->id ?? '')) == $ta->id ? 'selected' : '' }}>
                            {{ $ta->nama }} - {{ ucfirst($ta->semester) }} {{ $ta->aktif ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>

                <label>Kelas</label>
                <select name="kelas_id" required>
                    @foreach ($kelas as $k)
                        <option value="{{ $k->id }}"
                            {{ old('kelas_id', $jadwal->kelas_id) == $k->id ? 'selected' : '' }}>
                            {{ $k->nama_kelas }}
                        </option>
                    @endforeach
                </select>

                <label>Hari</label>
                <select name="hari" required>
                    @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h)
                        <option value="{{ $h }}" {{ old('hari', $jadwal->hari) == $h ? 'selected' : '' }}>
                            {{ $h }}
                        </option>
                    @endforeach
                </select>

                <div class="jp-panel">
                    <div>
                        <label>Mulai JP</label>
                        <select name="jam_ke_mulai" id="jamKeMulai" required>
                            <option value="">Pilih JP mulai</option>
                            @foreach ($slotJamPelajaran as $slot)
                                <option value="{{ $slot['jp'] }}"
                                    {{ old('jam_ke_mulai', $jadwal->jam_ke_mulai ?? '') == $slot['jp'] ? 'selected' : '' }}>
                                    {{ $slot['label'] }} ({{ $slot['mulai'] }} - {{ $slot['selesai'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label>Jumlah JP</label>
                        <input type="number" name="jumlah_jp" id="jumlahJp" min="1" max="8"
                            value="{{ old('jumlah_jp', $jadwal->jumlah_jp ?? '') }}" required>
                    </div>
                </div>

                <div class="time-preview">
                    <div>
                        <label>Jam Mulai</label>
                        <input type="time" name="jam_mulai" id="jamMulai"
                            value="{{ old('jam_mulai', substr($jadwal->jam_mulai, 0, 5)) }}" readonly required>
                    </div>

                    <div>
                        <label>Jam Selesai</label>
                        <input type="time" name="jam_selesai" id="jamSelesai"
                            value="{{ old('jam_selesai', substr($jadwal->jam_selesai, 0, 5)) }}" readonly required>
                    </div>
                </div>

                <div class="jp-help" id="jpHelp"></div>

                <label>Mata Pelajaran</label>
                <select name="mapel_id" required>
                    @foreach ($mapels as $m)
                        <option value="{{ $m->id }}"
                            {{ old('mapel_id', $jadwal->mapel_id) == $m->id ? 'selected' : '' }}>
                            {{ $m->nama_mapel }}
                        </option>
                    @endforeach
                </select>

                <label>Guru Utama</label>
                <select name="guru_id" required>
                    @foreach ($guru as $g)
                        <option value="{{ $g->id }}"
                            {{ old('guru_id', $jadwal->guru_id) == $g->id ? 'selected' : '' }}>
                            {{ $g->nama }}
                        </option>
                    @endforeach
                </select>

                <label>Status Guru</label>
                <select name="status_guru" required>
                    @foreach (['normal' => 'Normal', 'sakit' => 'Sakit', 'izin' => 'Izin', 'inval' => 'Inval', 'digantikan' => 'Digantikan'] as $value => $label)
                        <option value="{{ $value }}"
                            {{ old('status_guru', $jadwal->status_guru ?? 'normal') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <label>Guru Pengganti</label>
                <select name="guru_pengganti_id">
                    <option value="">Tidak ada</option>
                    @foreach ($guru as $g)
                        <option value="{{ $g->id }}"
                            {{ old('guru_pengganti_id', $jadwal->guru_pengganti_id) == $g->id ? 'selected' : '' }}>
                            {{ $g->nama }}
                        </option>
                    @endforeach
                </select>

                <label>Alasan Tidak Hadir</label>
                <input type="text" name="alasan_tidak_hadir"
                    value="{{ old('alasan_tidak_hadir', $jadwal->alasan_tidak_hadir) }}"
                    placeholder="Contoh: rapat, sakit, tugas luar">

                <label>Keterangan</label>
                <textarea name="keterangan">{{ old('keterangan', $jadwal->keterangan) }}</textarea>

                <button type="submit">Update Jadwal</button>
                <a href="/dashboard/admin/jadwal" class="back">Kembali</a>
            </form>
        </div>

    </main>
    <script>
        const jpSlots = @json($slotJamPelajaran);
        const breaks = [
            { nama: 'Istirahat 1', mulai: '09:20', selesai: '09:50' },
            { nama: 'Istirahat 2', mulai: '12:10', selesai: '12:50' },
        ];

        const jamKeMulai = document.getElementById('jamKeMulai');
        const jumlahJp = document.getElementById('jumlahJp');
        const jamMulai = document.getElementById('jamMulai');
        const jamSelesai = document.getElementById('jamSelesai');
        const jpHelp = document.getElementById('jpHelp');

        function updateJamPreview() {
            const start = Number(jamKeMulai.value);
            const count = Number(jumlahJp.value);
            const end = start + count - 1;
            const startSlot = jpSlots[start];
            const endSlot = jpSlots[end];

            if (!startSlot || !endSlot) {
                jamMulai.value = '';
                jamSelesai.value = '';
                jpHelp.textContent = 'Jumlah JP melewati batas slot yang tersedia.';
                jpHelp.className = 'jp-help error';
                return;
            }

            const crossesBreak = breaks.find((item) => startSlot.mulai < item.selesai && endSlot.selesai > item.mulai);

            jamMulai.value = startSlot.mulai;
            jamSelesai.value = endSlot.selesai;

            if (crossesBreak) {
                jpHelp.textContent = `Jadwal melewati ${crossesBreak.nama}. Pisahkan mapel sebelum atau sesudah istirahat.`;
                jpHelp.className = 'jp-help error';
                return;
            }

            jpHelp.textContent = `${count} JP: ${startSlot.mulai} - ${endSlot.selesai}`;
            jpHelp.className = 'jp-help';
        }

        jamKeMulai?.addEventListener('change', updateJamPreview);
        jumlahJp?.addEventListener('input', updateJamPreview);
        updateJamPreview();
    </script>
</body>

</html>
