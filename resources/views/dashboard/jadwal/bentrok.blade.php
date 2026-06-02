<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Deteksi Bentrok Jadwal</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-admin.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        <div class="welcome">
            <div>
                <h2>Deteksi Bentrok Jadwal</h2>
                <p>Scan data lama untuk menemukan jadwal pelajaran dan guru piket yang tumpang tindih.</p>
            </div>
            <a href="/dashboard/admin/jadwal" class="btn">Kembali</a>
        </div>

        <form method="GET" class="panel admin-form">
            <label>
                Tahun Ajaran
                <select name="tahun_ajaran_id">
                    <option value="">Semua Tahun Ajaran</option>
                    @foreach ($tahunAjaran as $ta)
                        <option value="{{ $ta->id }}" {{ ($tahunAjaranId ?? '') == $ta->id ? 'selected' : '' }}>
                            {{ $ta->nama }} - {{ ucfirst($ta->semester) }} {{ $ta->aktif ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </label>
            <button class="btn" type="submit">Scan Bentrok</button>
        </form>

        <div class="panel">
            <div class="panel-head">
                <div>
                    <h3>Hasil Scan</h3>
                    <p>{{ count($bentrok) }} potensi bentrok ditemukan.</p>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Tipe</th>
                        <th>Tahun Ajaran</th>
                        <th>Hari</th>
                        <th>Jam</th>
                        <th>Detail 1</th>
                        <th>Detail 2</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bentrok as $row)
                        <tr>
                            <td><span class="status-pill muted">{{ $row['tipe'] }}</span></td>
                            <td>{{ $row['tahun_ajaran'] }}</td>
                            <td>{{ ucfirst($row['hari']) }}</td>
                            <td>{{ $row['jam'] }}</td>
                            <td>{{ $row['detail_1'] }}</td>
                            <td>{{ $row['detail_2'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-table">Tidak ada bentrok pada filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>
