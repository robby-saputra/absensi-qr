<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Surat Wali Kelas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            margin: 0;
            color: #111827
        }

        .paper {
            max-width: 820px;
            margin: 28px auto;
            background: #fff;
            padding: 38px;
            border: 1px solid #e5e7eb
        }

        .kop {
            text-align: center;
            border-bottom: 3px solid #111827;
            padding-bottom: 14px;
            margin-bottom: 24px
        }

        .kop h2 {
            margin: 0;
            font-size: 22px
        }

        .kop p {
            margin: 6px 0 0
        }

        .meta {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 8px;
            margin: 18px 0
        }

        .box {
            border: 1px solid #e5e7eb;
            padding: 14px;
            margin: 16px 0
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 9px;
            text-align: left
        }

        th {
            background: #f9fafb
        }

        .actions {
            max-width: 820px;
            margin: 18px auto;
            text-align: right
        }

        .btn {
            background: #1f2937;
            color: white;
            padding: 10px 14px;
            border-radius: 6px;
            text-decoration: none;
            border: 0
        }

        @media print {
            .actions {
                display: none
            }

            body {
                background: #fff
            }

            .paper {
                margin: 0 auto;
                border: 0
            }
        }
    </style>
</head>

<body>
    <div class="actions"><button class="btn" onclick="window.print()">Print Surat</button></div>
    <main class="paper">
        <div class="kop">
            <h2>{{ \App\Services\AttendanceSettingService::namaSekolah() }}</h2>
            <p>Surat Panggilan / Laporan Pembinaan Wali Kelas</p>
        </div>
        <p>Kepada Yth. Orang Tua/Wali dari siswa berikut:</p>
        <div class="meta">
            <strong>Nama Siswa</strong><span>{{ $siswa->nama }}</span>
            <strong>Kelas</strong><span>{{ $wali->nama_kelas }}</span>
            <strong>Wali Kelas</strong><span>{{ $user->nama }}</span>
            <strong>Tanggal Surat</strong><span>{{ now()->locale('id')->translatedFormat('d F Y') }}</span>
        </div>
        <p>Dengan hormat, berdasarkan data absensi dan catatan pembinaan kelas, kami menyampaikan ringkasan kondisi
            kehadiran siswa selama 30 hari terakhir sebagai bahan perhatian bersama.</p>
        <div class="box">
            <h3>Ringkasan Kehadiran 30 Hari</h3>
            <table>
                <tr>
                    <th>Telat</th>
                    <th>Alfa</th>
                    <th>Izin</th>
                    <th>Sakit</th>
                </tr>
                <tr>
                    <td>{{ $rekap->telat ?? 0 }}</td>
                    <td>{{ $rekap->alfa ?? 0 }}</td>
                    <td>{{ $rekap->izin ?? 0 }}</td>
                    <td>{{ $rekap->sakit ?? 0 }}</td>
                </tr>
            </table>
        </div>
        <div class="box">
            <h3>Catatan Wali Kelas</h3>
            <table>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Catatan</th>
                </tr>
                @forelse($catatan as $c)
                    <tr>
                        <td>{{ $c->tanggal }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $c->kategori)) }}</td>
                        <td>{{ $c->catatan }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Belum ada catatan pembinaan tertulis.</td>
                    </tr>
                @endforelse
            </table>
        </div>
        <p>Demikian surat ini dibuat untuk menjadi perhatian dan tindak lanjut bersama. Atas kerja sama Bapak/Ibu, kami
            ucapkan terima kasih.</p>
        <br><br>
        <p style="text-align:right">Wali Kelas,<br><br><br><strong>{{ $user->nama }}</strong></p>
    </main>
</body>

</html>
