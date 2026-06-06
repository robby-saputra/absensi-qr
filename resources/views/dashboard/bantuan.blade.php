<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Bantuan</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            color: #172033;
        }

        .help-wrap {
            max-width: 1080px;
            margin: 0 auto;
            padding: 28px;
        }

        body.has-sidebar .help-wrap {
            margin-left: 278px;
            margin-right: 24px;
        }

        .help-hero {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-end;
            padding: 24px;
            border-radius: 8px;
            background: #273c75;
            color: #fff;
            box-shadow: 0 16px 38px rgba(15, 23, 42, .14);
            margin-bottom: 18px;
        }

        .help-hero h1 {
            margin: 0 0 8px;
            font-size: 32px;
        }

        .help-hero p {
            margin: 0;
            color: #dbeafe;
            line-height: 1.6;
            max-width: 760px;
        }

        .help-kicker {
            display: inline-flex;
            margin-bottom: 9px;
            color: #bbf7d0;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .help-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            min-height: 42px;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border-radius: 8px;
            background: #fff;
            color: #172033;
            text-decoration: none;
            font-weight: 800;
        }

        .help-note {
            padding: 14px 16px;
            border-left: 5px solid #27c69f;
            border-radius: 8px;
            background: #ecfdf3;
            color: #166534;
            font-weight: 700;
            line-height: 1.55;
            margin-bottom: 18px;
        }

        .help-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 14px;
        }

        .help-card {
            background: #fff;
            border: 1px solid #e5eaf2;
            border-radius: 8px;
            padding: 18px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .06);
        }

        .help-card h2 {
            margin: 0 0 10px;
            color: #111827;
            font-size: 20px;
        }

        .help-card p {
            margin: 0 0 12px;
            color: #64748b;
            line-height: 1.55;
        }

        .help-card ul {
            margin: 0;
            padding-left: 18px;
            color: #334155;
            line-height: 1.75;
        }

        .help-card li+li {
            margin-top: 4px;
        }

        .help-card strong {
            color: #172033;
        }

        .help-wide {
            grid-column: 1 / -1;
        }

        .role-guide {
            border-left: 5px solid #273c75;
        }

        @media(max-width:760px) {
            .help-wrap {
                margin-left: 0 !important;
                margin-right: 0 !important;
                padding: 18px;
            }

            .help-hero {
                display: grid;
                align-items: flex-start;
            }

            .help-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body class="{{ ($role ?? 'publik') !== 'publik' ? 'has-sidebar' : '' }}">
    @php
        $targetRole = $targetRole ?? ($role ?? 'publik');
        $backUrl = match ($role ?? 'publik') {
            'admin' => '/dashboard/admin',
            'piket' => '/dashboard/piket',
            'guru' => '/dashboard/guru',
            'siswa' => '/dashboard/users',
            default => '/login',
        };
    @endphp

    @if (($role ?? '') === 'admin')
        @include('layouts.sidebar_admin')
    @elseif($targetRole === 'piket')
        @include('layouts.sidebar_piket')
    @elseif($targetRole === 'wali')
        @include('layouts.sidebar_wali')
    @elseif(($role ?? '') === 'guru')
        @include('layouts.sidebar_guru')
    @endif

    <main class="help-wrap">
        <section class="help-hero">
            <div>
                <span class="help-kicker">Panduan Singkat</span>
                <h1>Pusat Bantuan Absensi QR</h1>
                <p>Halaman ini berisi panduan penggunaan sistem absensi siswa berbasis QR Code untuk website Laravel dan
                    aplikasi Android Flutter.</p>
            </div>
            <div class="help-actions">
                <a class="btn" href="{{ $backUrl }}">Kembali</a>
                @if (($role ?? '') === 'publik')
                    <a class="btn" href="/login">Masuk Sistem</a>
                @endif
            </div>
        </section>

        <div class="help-note">
            Website digunakan oleh admin dan guru. Aplikasi Android digunakan oleh siswa dan orang tua. Validasi QR dan
            lokasi absensi ditentukan oleh Laravel/backend.
        </div>

        <section class="help-grid">
            @if ($targetRole !== 'publik')
                <article class="help-card help-wide role-guide">
                    @if ($targetRole === 'admin')
                        <h2>Panduan Admin</h2>
                        <ul>
                            <li>Pastikan data siswa, guru, orang tua, kelas, jurusan, mata pelajaran, guru piket, wali kelas, dan jadwal sudah benar.</li>
                            <li>Buka Pengaturan Absensi untuk mengatur tahun ajaran aktif, semester aktif, jam masuk, batas telat, jam kunci absensi, koordinat sekolah, dan radius absensi.</li>
                            <li>Pantau pengajuan izin/sakit dari menu Pengajuan Izin/Sakit dan lakukan persetujuan sesuai bukti siswa.</li>
                            <li>Gunakan rekap/laporan absensi untuk melihat absensi harian dan absensi mata pelajaran.</li>
                            <li>Jika data sudah melewati Jam Kunci Absensi, koreksi hanya dilakukan oleh admin melalui panel admin.</li>
                            <li>Lonceng notifikasi admin dipakai untuk melihat pemberitahuan penting seperti pengajuan izin/sakit dan informasi absensi.</li>
                        </ul>
                    @elseif($targetRole === 'piket')
                        <h2>Panduan Guru Piket</h2>
                        <ul>
                            <li>Buka dashboard guru piket untuk melihat jadwal piket dan QR absensi harian.</li>
                            <li>Generate QR masuk atau pulang sesuai kebutuhan absensi harian.</li>
                            <li>Pantau daftar siswa yang belum masuk, belum pulang, izin, sakit, telat, atau alfa.</li>
                            <li>Koreksi absensi harian hanya dapat dilakukan sebelum Jam Kunci Absensi.</li>
                            <li>Setelah melewati Jam Kunci Absensi, data terkunci otomatis dan koreksi harus melalui admin.</li>
                            <li>Proses pengajuan izin/sakit siswa jika muncul pada menu pengajuan izin.</li>
                        </ul>
                    @elseif($targetRole === 'guru')
                        <h2>Panduan Guru Mata Pelajaran</h2>
                        <ul>
                            <li>Buka dashboard guru untuk melihat jadwal mengajar hari ini.</li>
                            <li>Mulai sesi mapel untuk menampilkan QR absensi mata pelajaran.</li>
                            <li>Minta siswa scan QR mapel melalui aplikasi Android.</li>
                            <li>Buka Verifikasi Absen Mapel untuk memantau siswa yang sudah atau belum scan.</li>
                            <li>Koreksi absensi mapel hanya dapat dilakukan sebelum Jam Kunci Absensi.</li>
                            <li>Setelah melewati Jam Kunci Absensi, data terkunci otomatis dan koreksi harus melalui admin.</li>
                        </ul>
                    @elseif($targetRole === 'wali')
                        <h2>Panduan Wali Kelas</h2>
                        <ul>
                            <li>Buka dashboard wali kelas untuk memantau ringkasan kehadiran siswa di kelas.</li>
                            <li>Gunakan menu Data Siswa untuk melihat data siswa dan informasi orang tua.</li>
                            <li>Gunakan menu Absensi Siswa untuk melihat riwayat absensi harian siswa kelas.</li>
                            <li>Pantau siswa yang sering telat, alfa, izin, atau sakit sebagai bahan tindak lanjut.</li>
                            <li>Jika ada data absensi yang perlu dikoreksi setelah Jam Kunci Absensi, hubungi admin sekolah yang berwenang melakukan koreksi.</li>
                        </ul>
                    @elseif($targetRole === 'siswa')
                        <h2>Panduan Siswa</h2>
                        <ul>
                            <li>Login menggunakan akun siswa yang diberikan sekolah.</li>
                            <li>Gunakan aplikasi Android untuk scan QR absensi harian dan absensi mata pelajaran.</li>
                            <li>Aktifkan GPS/lokasi dan izinkan akses lokasi saat scan QR.</li>
                            <li>Ajukan izin/sakit melalui sistem jika tidak dapat hadir.</li>
                            <li>Lihat riwayat absensi dan status pengajuan untuk memastikan data sudah tercatat.</li>
                        </ul>
                    @endif
                </article>
            @endif

            <article class="help-card">
                <h2>Tentang Sistem</h2>
                <ul>
                    <li>Sistem ini digunakan untuk absensi siswa berbasis QR Code.</li>
                    <li>Website digunakan oleh admin, guru piket, guru mata pelajaran, dan wali kelas.</li>
                    <li>Aplikasi Android digunakan oleh siswa dan orang tua.</li>
                    <li>Radius lokasi absensi default adalah 200 meter dan diatur dari Pengaturan Absensi.</li>
                    <li>Absensi harian dan absensi mata pelajaran terkunci otomatis setelah Jam Kunci Absensi.</li>
                </ul>
            </article>

            <article class="help-card">
                <h2>Panduan Login</h2>
                <ul>
                    <li>Admin, guru piket, guru mata pelajaran, dan wali kelas login melalui website.</li>
                    <li>Siswa dan orang tua login melalui aplikasi Android.</li>
                    <li>Jika lupa username atau password, hubungi admin sekolah.</li>
                </ul>
            </article>

            <article class="help-card">
                <h2>Panduan Siswa</h2>
                <ul>
                    <li>Login melalui aplikasi Android.</li>
                    <li>Pilih menu scan QR untuk absensi harian atau absensi mata pelajaran.</li>
                    <li>Aktifkan GPS/lokasi dan izinkan akses lokasi.</li>
                    <li>Pastikan berada di area sekolah.</li>
                    <li>Jika QR tidak valid atau lokasi di luar radius, absensi tidak diproses.</li>
                    <li>Jika ada kesalahan data setelah Jam Kunci Absensi, hubungi admin sekolah.</li>
                </ul>
            </article>

            <article class="help-card">
                <h2>Panduan Orang Tua</h2>
                <ul>
                    <li>Login melalui aplikasi Android.</li>
                    <li>Orang tua dapat melihat riwayat kehadiran anak.</li>
                    <li>Orang tua menerima notifikasi status kehadiran melalui aplikasi.</li>
                </ul>
            </article>

            <article class="help-card">
                <h2>Panduan Izin/Sakit</h2>
                <ul>
                    <li>Siswa dapat mengajukan izin/sakit melalui aplikasi.</li>
                    <li>Pengajuan akan diproses oleh pihak sekolah.</li>
                    <li>Status pengajuan dapat dilihat melalui sistem.</li>
                </ul>
            </article>

            <article class="help-card">
                <h2>Kunci Otomatis Absensi</h2>
                <ul>
                    <li>Guru piket dan guru mata pelajaran dapat melakukan koreksi sebelum Jam Kunci Absensi.</li>
                    <li>Setelah melewati jam tersebut, absensi harian dan absensi mata pelajaran otomatis terkunci.</li>
                    <li>Data yang sudah terkunci hanya bisa diubah oleh admin sekolah.</li>
                    <li>Jam kunci dapat diatur admin melalui menu Pengaturan Absensi. Defaultnya adalah 14:00.</li>
                    <li>Aturan ini berlaku otomatis berdasarkan jam yang ditentukan admin.</li>
                </ul>
            </article>

            <article class="help-card">
                <h2>Kendala Umum</h2>
                <ul>
                    <li><strong>Tidak bisa login:</strong> periksa username/password atau hubungi admin.</li>
                    <li><strong>QR tidak terbaca:</strong> pastikan kamera jelas dan QR masih berlaku.</li>
                    <li><strong>Lokasi gagal:</strong> aktifkan GPS dan izinkan akses lokasi.</li>
                    <li><strong>Notifikasi tidak masuk:</strong> pastikan internet aktif dan aplikasi mengizinkan
                        notifikasi.</li>
                    <li><strong>Data tidak muncul:</strong> coba refresh atau login ulang.</li>
                </ul>
            </article>

            <article class="help-card help-wide">
                <h2>Kontak Bantuan</h2>
                <p>Untuk kendala akun atau data absensi, hubungi admin sekolah.</p>
            </article>
        </section>
    </main>
</body>

</html>
