<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Edit Guru Piket</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-guru_piket-create.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content">

        <div class="container">
            <h1>Edit Guru Piket</h1>

            <a href="/dashboard/admin/guru-piket" class="btn-kembali">Kembali</a>

            @if (session('error'))
                <div class="alert">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert">{{ $errors->first() }}</div>
            @endif

            <form action="/dashboard/admin/guru-piket/update/{{ $guruPiket->id }}" method="POST">
                @csrf

                <label>Tahun Ajaran</label>
                <select name="tahun_ajaran_id">
                    @foreach ($tahunAjaran as $ta)
                        <option value="{{ $ta->id }}"
                            {{ old('tahun_ajaran_id', $guruPiket->tahun_ajaran_id ?? ($tahunAjaranAktif->id ?? '')) == $ta->id ? 'selected' : '' }}>
                            {{ $ta->nama }} - {{ ucfirst($ta->semester) }} {{ $ta->aktif ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>

                <label>Guru Piket</label>
                <select name="guru_id" required>
                    @foreach ($guru as $g)
                        <option value="{{ $g->id }}"
                            {{ old('guru_id', $guruPiket->guru_id) == $g->id ? 'selected' : '' }}>
                            {{ $g->nama }} ({{ $g->username }})
                        </option>
                    @endforeach
                </select>

                <label>Guru Pengganti 1</label>
                <select name="guru_pengganti_id">
                    <option value="">Tidak Ada</option>
                    @foreach ($guru as $g)
                        <option value="{{ $g->id }}"
                            {{ old('guru_pengganti_id', $guruPiket->guru_pengganti_id) == $g->id ? 'selected' : '' }}>
                            {{ $g->nama }} ({{ $g->username }})
                        </option>
                    @endforeach
                </select>

                <label>Guru Pengganti 2</label>
                <select name="guru_pengganti2_id">
                    <option value="">Tidak Ada</option>
                    @foreach ($guru as $g)
                        <option value="{{ $g->id }}"
                            {{ old('guru_pengganti2_id', $guruPiket->guru_pengganti2_id) == $g->id ? 'selected' : '' }}>
                            {{ $g->nama }} ({{ $g->username }})
                        </option>
                    @endforeach
                </select>

                <label>Hari</label>
                <select name="hari" required>
                    @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h)
                        <option value="{{ $h }}"
                            {{ old('hari', ucfirst($guruPiket->hari)) == $h ? 'selected' : '' }}>
                            {{ $h }}
                        </option>
                    @endforeach
                </select>

                <label>Jam Mulai</label>
                <input type="time" name="jam_mulai"
                    value="{{ old('jam_mulai', substr($guruPiket->jam_mulai, 0, 5)) }}" required>

                <label>Jam Selesai</label>
                <input type="time" name="jam_selesai"
                    value="{{ old('jam_selesai', substr($guruPiket->jam_selesai, 0, 5)) }}" required>

                <label>Status</label>
                <select name="status" required>
                    @foreach (['Akan Bertugas', 'Sedang Bertugas', 'Izin', 'Sakit', 'Digantikan', 'Selesai'] as $status)
                        <option value="{{ $status }}"
                            {{ old('status', $guruPiket->status) == $status ? 'selected' : '' }}>
                            {{ $status }}
                        </option>
                    @endforeach
                </select>

                <label class="guru-item">
                    <input type="checkbox" name="aktif" value="1"
                        {{ old('aktif', $guruPiket->aktif) ? 'checked' : '' }}>
                    <div>Aktif</div>
                </label>

                <button type="submit" class="btn btn-simpan">Update Guru Piket</button>
            </form>
        </div>

    </main>
</body>

</html>
