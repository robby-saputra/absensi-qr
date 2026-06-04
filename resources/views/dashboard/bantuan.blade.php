<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Bantuan</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
    <style>
        body {
            background: #f5f6fa;
            color: #172033
        }

        .help-public .content {
            margin-left: 0;
            max-width: 1180px;
            margin-right: auto;
            margin-left: auto
        }

        .help-hero {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-end;
            padding: 24px;
            border-radius: 8px;
            background: linear-gradient(135deg, #172033, #273c75 62%, #22c7a0);
            color: #fff;
            box-shadow: 0 16px 40px rgba(15, 23, 42, .14);
            margin-bottom: 18px
        }

        .help-hero h1 {
            margin: 0 0 8px;
            color: #fff;
            font-size: 32px
        }

        .help-hero p {
            margin: 0;
            color: #dbeafe;
            line-height: 1.6;
            max-width: 760px
        }

        .help-kicker {
            display: inline-flex;
            margin-bottom: 9px;
            color: #bbf7d0;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase
        }

        .help-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap
        }

        .help-actions .btn {
            background: #fff !important;
            color: #172033 !important
        }

        .help-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 14px;
            margin-bottom: 18px
        }

        .help-card {
            background: #fff;
            border: 1px solid #e5eaf2;
            border-radius: 8px;
            padding: 18px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .06)
        }

        .help-card h2,
        .help-card h3 {
            margin: 0 0 10px;
            color: #111827
        }

        .help-card p {
            margin: 0 0 12px;
            color: #64748b;
            line-height: 1.55
        }

        .help-card ol,
        .help-card ul {
            margin: 0;
            padding-left: 18px;
            color: #334155;
            line-height: 1.75
        }

        .help-card li+li {
            margin-top: 4px
        }

        .help-card strong {
            color: #172033
        }

        .role-badge {
            display: inline-flex;
            padding: 6px 10px;
            border-radius: 999px;
            background: #eef2ff;
            color: #273c75;
            font-weight: 900;
            font-size: 12px;
            margin-bottom: 10px
        }

        .role-badge.mobile {
            background: #ecfdf3;
            color: #166534
        }

        .role-badge.warn {
            background: #fff7ed;
            color: #9a3412
        }

        .quick-links {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px
        }

        .help-wide {
            grid-column: 1 / -1
        }

        .help-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 12px
        }

        .step-box {
            border: 1px solid #eef2f7;
            border-radius: 8px;
            padding: 14px;
            background: #f8fafc
        }

        .step-box span {
            display: inline-flex;
            width: 28px;
            height: 28px;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #27c69f;
            color: #fff;
            font-weight: 900;
            margin-bottom: 9px
        }

        .help-note {
            padding: 14px 16px;
            border-left: 5px solid #27c69f;
            border-radius: 8px;
            background: #ecfdf3;
            color: #166534;
            font-weight: 700;
            line-height: 1.55;
            margin-bottom: 18px
        }

        .context-panel {
            background: #fff;
            border: 1px solid #dbeafe;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 16px 36px rgba(15, 23, 42, .08);
            margin-bottom: 18px
        }

        .context-panel h2 {
            margin: 0 0 8px;
            color: #172033
        }

        .context-panel p {
            margin: 0 0 14px;
            color: #64748b;
            line-height: 1.6
        }

        .context-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 12px
        }

        .context-box {
            border: 1px solid #eef2f7;
            border-radius: 8px;
            background: #f8fafc;
            padding: 14px
        }

        .context-box h3 {
            margin: 0 0 8px;
            color: #172033
        }

        .context-box ol,
        .context-box ul {
            margin: 0;
            padding-left: 18px;
            color: #334155;
            line-height: 1.7
        }

        @media(max-width:820px) {
            .help-hero {
                display: grid
            }

            .content {
                padding: 18px !important
            }

            .help-actions .btn {
                width: 100%
            }
        }
    </style>
</head>

<body class="{{ ($role ?? '') === 'publik' ? 'help-public' : '' }}">
    @if (($role ?? '') === 'admin')
        @include('layouts.sidebar_admin')
    @elseif(($role ?? '') === 'piket')
        @include('layouts.sidebar_piket')
    @elseif(($role ?? '') === 'guru')
        @include('layouts.sidebar_guru')
    @endif

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

    <main id="content" class="content">
        <section class="help-hero">
            <div>
                <span class="help-kicker">Panduan Sistem</span>
                <h1>Pusat Bantuan Absensi QR</h1>
                <p>Pelajari cara memakai sistem web dan aplikasi mobile untuk absensi harian, absensi mapel, pengajuan
                    izin/sakit, rekap, notifikasi, dan pemantauan orang tua.</p>
            </div>
            <div class="help-actions">
                <a class="btn back" href="{{ $backUrl }}">Kembali</a>
                @if (($role ?? '') === 'publik')
                    <a class="btn" href="/login">Masuk Sistem</a>
                @endif
            </div>
        </section>

        <div class="help-note">
            Sistem ini dipakai bersama oleh admin, guru piket, guru mapel, wali kelas, siswa, dan orang tua. Data harian
            dari QR masuk/pulang menjadi dasar absensi mapel, rekap, validasi bulanan, dan notifikasi.
        </div>

        @if ($targetRole !== 'publik')
            <section class="context-panel">
                @if ($targetRole === 'admin')
                    <span class="role-badge">Panduan Superadmin</span>
                    <h2>Pusat Bantuan untuk Superadmin</h2>
                    <p>Bagian ini fokus untuk admin yang mengelola data induk, keamanan, arsip, rekap, validasi bulanan,
                        dan kesiapan sistem sebelum dipakai role lain.</p>
                    <div class="context-grid">
                        <div class="context-box">
                            <h3>Persiapan Awal</h3>
                            <ol>
                                <li>Aktifkan tahun ajaran.</li>
                                <li>Input jurusan, kelas, guru, siswa, wali kelas, dan guru piket.</li>
                                <li>Lengkapi jadwal pelajaran dan kalender sekolah.</li>
                                <li>Cek data kosong atau bentrok di menu kesehatan data.</li>
                            </ol>
                        </div>
                        <div class="context-box">
                            <h3>Operasional Harian</h3>
                            <ol>
                                <li>Pantau pengajuan izin/sakit.</li>
                                <li>Cek notifikasi dan audit log.</li>
                                <li>Pastikan QR harian dan QR mapel berjalan lewat role piket/guru.</li>
                                <li>Gunakan arsip untuk restore data yang salah hapus.</li>
                            </ol>
                        </div>
                        <div class="context-box">
                            <h3>Akhir Bulan</h3>
                            <ol>
                                <li>Buka Validasi Tutup Bulan.</li>
                                <li>Perbaiki siswa belum pulang, belum mapel, alfa, atau izin yang belum direview.</li>
                                <li>Kunci laporan jika masalah sudah nol.</li>
                                <li>Backup database setelah data final.</li>
                            </ol>
                        </div>
                    </div>
                @elseif($targetRole === 'piket')
                    <span class="role-badge">Panduan Guru Piket</span>
                    <h2>Pusat Bantuan untuk Guru Piket</h2>
                    <p>Bagian ini fokus untuk guru piket yang mengatur absensi harian, QR masuk/pulang, koreksi
                        kehadiran, dan pengajuan izin/sakit siswa.</p>
                    <div class="context-grid">
                        <div class="context-box">
                            <h3>Sebelum Jam Masuk</h3>
                            <ol>
                                <li>Buka Dashboard Guru Piket.</li>
                                <li>Pastikan Anda memang punya jadwal piket aktif.</li>
                                <li>Buat QR masuk.</li>
                                <li>Arahkan siswa scan QR saat tiba di sekolah.</li>
                            </ol>
                        </div>
                        <div class="context-box">
                            <h3>Saat Berjalan</h3>
                            <ol>
                                <li>Pantau siswa yang belum absen masuk.</li>
                                <li>Review pengajuan izin/sakit jika masuk ke menu piket.</li>
                                <li>Koreksi data hanya jika ada kesalahan scan atau kondisi khusus.</li>
                                <li>Isi catatan/alasan supaya tercatat di audit log.</li>
                            </ol>
                        </div>
                        <div class="context-box">
                            <h3>Jam Pulang</h3>
                            <ol>
                                <li>Buat QR pulang.</li>
                                <li>Pastikan siswa scan sebelum pulang.</li>
                                <li>Cek daftar belum pulang.</li>
                                <li>Finalisasi absensi harian jika data sudah benar.</li>
                            </ol>
                        </div>
                        <div class="context-box">
                            <h3>Efek ke Mobile</h3>
                            <ul>
                                <li>Orang tua menerima notifikasi saat siswa absen masuk atau pulang.</li>
                                <li>Siswa melihat status masuk/pulang di dashboard mobile.</li>
                                <li>Guru mapel memakai data harian ini sebagai acuan absensi mapel.</li>
                            </ul>
                        </div>
                    </div>
                @elseif($targetRole === 'guru')
                    <span class="role-badge">Panduan Guru Mapel</span>
                    <h2>Pusat Bantuan untuk Guru Mapel</h2>
                    <p>Bagian ini fokus untuk guru mapel yang mengelola QR mapel, verifikasi kehadiran pelajaran, dan
                        rekap pembelajaran.</p>
                    <div class="context-grid">
                        <div class="context-box">
                            <h3>Mulai Mengajar</h3>
                            <ol>
                                <li>Buka Dashboard Guru.</li>
                                <li>Cek Jadwal Hari Ini.</li>
                                <li>Buat QR untuk sesi mapel yang sedang berlangsung.</li>
                                <li>Minta siswa scan QR mapel.</li>
                            </ol>
                        </div>
                        <div class="context-box">
                            <h3>Verifikasi</h3>
                            <ol>
                                <li>Buka Verifikasi Absen Mapel.</li>
                                <li>Cek siswa yang belum scan.</li>
                                <li>Perhatikan status dari absensi harian: izin, sakit, alfa, atau belum absen.</li>
                                <li>Jangan mengubah status yang seharusnya menjadi wewenang piket/admin.</li>
                            </ol>
                        </div>
                        <div class="context-box">
                            <h3>Finalisasi</h3>
                            <ol>
                                <li>Pastikan semua siswa sudah sesuai.</li>
                                <li>Isi catatan guru jika diperlukan.</li>
                                <li>Finalisasi sesi agar data terkunci.</li>
                                <li>Lihat Rekap Absen Mapel untuk laporan.</li>
                            </ol>
                        </div>
                        <div class="context-box">
                            <h3>Efek ke Mobile</h3>
                            <ul>
                                <li>Siswa melihat jadwal mapel dan status sudah/belum absen.</li>
                                <li>Orang tua bisa menerima notifikasi saat siswa absen mapel.</li>
                                <li>Data masuk ke rekap mapel.</li>
                            </ul>
                        </div>
                    </div>
                @elseif($targetRole === 'wali')
                    <span class="role-badge">Panduan Wali Kelas</span>
                    <h2>Pusat Bantuan untuk Wali Kelas</h2>
                    <p>Bagian ini fokus untuk wali kelas yang memantau siswa, absensi kelas, siswa rawan, pembinaan, dan
                        validasi bulanan.</p>
                    <div class="context-grid">
                        <div class="context-box">
                            <h3>Pantau Kelas</h3>
                            <ol>
                                <li>Buka Dashboard Wali Kelas.</li>
                                <li>Cek ringkasan hadir, telat, izin, sakit, dan alfa.</li>
                                <li>Buka Data Siswa untuk melihat profil dan kontak orang tua.</li>
                                <li>Cek Absensi Siswa untuk melihat riwayat.</li>
                            </ol>
                        </div>
                        <div class="context-box">
                            <h3>Tindak Lanjut</h3>
                            <ol>
                                <li>Identifikasi siswa rawan telat/alfa.</li>
                                <li>Tambahkan catatan pembinaan.</li>
                                <li>Cetak surat jika perlu panggilan orang tua.</li>
                                <li>Koordinasi lewat pesan internal/notifikasi.</li>
                            </ol>
                        </div>
                        <div class="context-box">
                            <h3>Validasi Bulanan</h3>
                            <ol>
                                <li>Buka Validasi Bulanan.</li>
                                <li>Cek data belum pulang, belum mapel, alfa, dan izin yang belum selesai.</li>
                                <li>Koordinasi perbaikan dengan piket/guru/admin.</li>
                                <li>Kunci jika sudah valid sesuai wewenang.</li>
                            </ol>
                        </div>
                    </div>
                @elseif($targetRole === 'siswa')
                    <span class="role-badge">Panduan Siswa</span>
                    <h2>Pusat Bantuan untuk Siswa</h2>
                    <p>Bagian ini fokus untuk siswa yang memakai dashboard web atau aplikasi mobile untuk scan QR,
                        melihat jadwal, dan mengajukan izin/sakit.</p>
                    <div class="context-grid">
                        <div class="context-box">
                            <h3>Absensi Harian</h3>
                            <ol>
                                <li>Login memakai akun siswa.</li>
                                <li>Scan QR masuk saat datang.</li>
                                <li>Scan QR pulang saat pulang sekolah.</li>
                                <li>Cek status masuk/pulang di dashboard.</li>
                            </ol>
                        </div>
                        <div class="context-box">
                            <h3>Absensi Mapel</h3>
                            <ol>
                                <li>Lihat jadwal hari ini.</li>
                                <li>Scan QR mapel dari guru.</li>
                                <li>Pastikan status mapel berubah menjadi sudah absen.</li>
                                <li>Laporkan ke guru jika QR gagal discan.</li>
                            </ol>
                        </div>
                        <div class="context-box">
                            <h3>Izin/Sakit</h3>
                            <ol>
                                <li>Buka menu pengajuan izin/sakit.</li>
                                <li>Pilih jenis, tanggal mulai, tanggal selesai, dan alasan.</li>
                                <li>Upload bukti JPG, PNG, atau PDF jika ada.</li>
                                <li>Cek status pengajuan: menunggu, disetujui, atau ditolak.</li>
                            </ol>
                        </div>
                        <div class="context-box">
                            <h3>Aplikasi Mobile</h3>
                            <ul>
                                <li>Melihat kartu pelajar digital.</li>
                                <li>Melihat kalender sekolah.</li>
                                <li>Menerima notifikasi belum absen atau hasil pengajuan.</li>
                                <li>Orang tua bisa menerima notifikasi absensi.</li>
                            </ul>
                        </div>
                    </div>
                @endif
            </section>
        @endif

        <section class="help-grid">
            <article class="help-card">
                <span class="role-badge">Superadmin</span>
                <h3>Alur Kerja Admin</h3>
                <ol>
                    <li>Atur data dasar: siswa, guru, kelas, jurusan, wali kelas, guru piket, tahun ajaran, jadwal, dan
                        kalender sekolah.</li>
                    <li>Pastikan kalender libur benar agar siswa tidak otomatis dianggap alfa pada hari libur.</li>
                    <li>Kelola pengajuan izin/sakit dari siswa. Jika disetujui, data absensi akan mengikuti status
                        izin/sakit.</li>
                    <li>Pantau audit log, kesehatan data, notifikasi, arsip data, dan keamanan akun.</li>
                    <li>Sebelum tutup bulan, buka Validasi Tutup Bulan. Jika tidak ada masalah, kunci laporan.</li>
                </ol>
                @if (($role ?? '') === 'admin')
                    <div class="quick-links">
                        <a class="btn" href="/dashboard/admin/users">Kelola Users</a>
                        <a class="btn" href="/dashboard/validasi-tutup-bulan">Validasi Bulan</a>
                        <a class="btn" href="/dashboard/admin/backup">Backup</a>
                    </div>
                @endif
            </article>

            <article class="help-card">
                <span class="role-badge">Guru Piket</span>
                <h3>Absensi Harian</h3>
                <ol>
                    <li>Buka dashboard guru piket saat bertugas.</li>
                    <li>Buat QR masuk untuk siswa yang datang ke sekolah.</li>
                    <li>Buat QR pulang saat jam pulang sekolah.</li>
                    <li>Pantau siswa yang belum absen masuk atau belum absen pulang.</li>
                    <li>Koreksi data hanya jika perlu, lalu isi alasan perubahan agar tercatat di audit log.</li>
                    <li>Finalisasi absensi harian jika data sudah benar.</li>
                </ol>
            </article>

            <article class="help-card">
                <span class="role-badge">Guru Mapel</span>
                <h3>Absensi Mapel</h3>
                <ol>
                    <li>Cek jadwal mengajar hari ini.</li>
                    <li>Buat QR absensi mapel sesuai sesi pelajaran.</li>
                    <li>Siswa scan QR mapel untuk tercatat hadir di pelajaran tersebut.</li>
                    <li>Verifikasi siswa yang belum scan berdasarkan data absensi harian.</li>
                    <li>Status izin, sakit, atau alfa dari piket menjadi acuan dan tidak boleh diubah sembarangan.</li>
                    <li>Finalisasi sesi mapel setelah data lengkap.</li>
                </ol>
            </article>

            <article class="help-card">
                <span class="role-badge">Wali Kelas</span>
                <h3>Pemantauan Kelas</h3>
                <ol>
                    <li>Pantau ringkasan hadir, telat, izin, sakit, alfa, dan siswa rawan.</li>
                    <li>Buka data siswa untuk melihat profil, kelas, orang tua, dan riwayat absensi.</li>
                    <li>Gunakan catatan pembinaan untuk tindak lanjut siswa yang sering bermasalah.</li>
                    <li>Cetak surat pembinaan jika diperlukan.</li>
                    <li>Cek validasi bulanan sebelum laporan dikunci.</li>
                </ol>
            </article>

            <article class="help-card">
                <span class="role-badge">Siswa Web</span>
                <h3>Dashboard Siswa</h3>
                <ol>
                    <li>Login memakai username dan kata sandi dari sekolah.</li>
                    <li>Lihat status absensi hari ini: masuk, pulang, dan status kehadiran.</li>
                    <li>Ajukan izin atau sakit dengan tanggal, alasan, dan bukti jika ada.</li>
                    <li>Cek riwayat pengajuan untuk melihat status menunggu, disetujui, atau ditolak.</li>
                    <li>Cek riwayat absensi harian dan absensi mapel.</li>
                </ol>
            </article>

            <article class="help-card">
                <span class="role-badge mobile">Aplikasi Mobile Siswa</span>
                <h3>Fitur Mobile untuk Siswa</h3>
                <ol>
                    <li><strong>Login siswa:</strong> masuk memakai akun siswa yang terdaftar di sistem.</li>
                    <li><strong>Dashboard harian:</strong> melihat jam masuk, jam pulang, status masuk, status pulang,
                        dan catatan piket.</li>
                    <li><strong>Jadwal hari ini:</strong> melihat mata pelajaran, jam mulai, jam selesai, guru utama,
                        serta status sudah/belum absen mapel.</li>
                    <li><strong>Pengajuan izin/sakit:</strong> mengirim tanggal mulai, tanggal selesai, jenis
                        izin/sakit, alasan, dan bukti berupa JPG, PNG, atau PDF.</li>
                    <li><strong>Kalender sekolah:</strong> melihat libur, kegiatan, ujian, dan agenda berulang seperti
                        hari libur mingguan.</li>
                    <li><strong>Kartu pelajar:</strong> menampilkan kode siswa, nama sekolah, logo sekolah, dan
                        identitas siswa.</li>
                    <li><strong>Notifikasi:</strong> melihat peringatan belum absen masuk, belum absen pulang, belum
                        absen mapel, hasil pengajuan, dan agenda kalender.</li>
                </ol>
            </article>

            <article class="help-card">
                <span class="role-badge mobile">Aplikasi Mobile Orang Tua</span>
                <h3>Fitur Mobile untuk Orang Tua</h3>
                <ol>
                    <li><strong>Login orang tua:</strong> bisa memakai nomor orang tua atau NIS siswa sesuai data yang
                        tersimpan.</li>
                    <li><strong>Pantau anak:</strong> melihat nama siswa, kelas, jurusan, wali kelas, dan status akun.
                    </li>
                    <li><strong>Notifikasi absensi:</strong> orang tua menerima notifikasi saat siswa absen masuk, absen
                        pulang, atau absen mapel.</li>
                    <li><strong>Riwayat notifikasi:</strong> aplikasi dapat menampilkan riwayat notifikasi orang tua
                        yang tersimpan di sistem.</li>
                    <li><strong>Token perangkat:</strong> aplikasi menyimpan token FCM agar notifikasi bisa dikirim ke
                        perangkat orang tua.</li>
                </ol>
            </article>

            <article class="help-card">
                <span class="role-badge mobile">Multi Role Mobile</span>
                <h3>Konteks Akses Mobile</h3>
                <p>Aplikasi mobile juga dapat membaca konteks role user. Sistem mengecek apakah user adalah admin, guru
                    mapel, wali kelas, guru piket, atau siswa.</p>
                <ul>
                    <li>Admin diarahkan ke fitur pengelolaan utama.</li>
                    <li>Guru mapel melihat akses jadwal dan absensi mapel.</li>
                    <li>Guru piket melihat akses piket jika punya jadwal aktif.</li>
                    <li>Wali kelas melihat akses pemantauan kelas.</li>
                    <li>Siswa melihat fitur scan QR, izin/sakit, dan riwayat.</li>
                </ul>
            </article>

            <article class="help-card help-wide">
                <span class="role-badge warn">Cara Pakai Ringkas</span>
                <h2>Urutan Pemakaian Harian</h2>
                <div class="help-steps">
                    <div class="step-box"><span>1</span>
                        <h3>Admin Siapkan Data</h3>
                        <p>Pastikan siswa, guru, kelas, jadwal, piket, kalender, dan tahun ajaran sudah benar.</p>
                    </div>
                    <div class="step-box"><span>2</span>
                        <h3>Piket Buat QR</h3>
                        <p>Guru piket membuat QR masuk dan pulang untuk absensi harian siswa.</p>
                    </div>
                    <div class="step-box"><span>3</span>
                        <h3>Siswa Scan QR</h3>
                        <p>Siswa scan QR harian dan QR mapel sesuai jadwal.</p>
                    </div>
                    <div class="step-box"><span>4</span>
                        <h3>Guru Verifikasi</h3>
                        <p>Guru mapel memeriksa kehadiran mapel dan finalisasi sesi.</p>
                    </div>
                    <div class="step-box"><span>5</span>
                        <h3>Wali Pantau</h3>
                        <p>Wali kelas melihat siswa rawan, riwayat, dan tindak lanjut pembinaan.</p>
                    </div>
                    <div class="step-box"><span>6</span>
                        <h3>Laporan Dikunci</h3>
                        <p>Admin atau wali melakukan validasi bulanan sebelum laporan dikunci.</p>
                    </div>
                </div>
            </article>

            <article class="help-card">
                <span class="role-badge warn">Tips Aman</span>
                <h3>Keamanan Akun</h3>
                <ul>
                    <li>Jangan membagikan username dan kata sandi.</li>
                    <li>Jika lupa kata sandi, hubungi admin untuk Atur Ulang.</li>
                    <li>Admin sebaiknya backup database sebelum import atau perubahan besar.</li>
                    <li>Gunakan audit log untuk melacak perubahan data penting.</li>
                    <li>Pastikan data orang tua benar agar notifikasi mobile sampai ke perangkat yang tepat.</li>
                </ul>
            </article>

            <article class="help-card">
                <span class="role-badge warn">Masalah Umum</span>
                <h3>Jika Ada Kendala</h3>
                <ul>
                    <li><strong>Tidak bisa login:</strong> cek username, kata sandi, dan status akun aktif.</li>
                    <li><strong>QR tidak bisa discan:</strong> pastikan QR masih aktif dan sesuai tipe masuk, pulang,
                        atau mapel.</li>
                    <li><strong>Jadwal tidak muncul:</strong> cek kelas, guru, hari, dan tahun ajaran.</li>
                    <li><strong>Notifikasi orang tua tidak masuk:</strong> pastikan aplikasi sudah mendaftarkan token
                        perangkat dan koneksi internet aktif.</li>
                    <li><strong>Data salah:</strong> minta admin/guru terkait melakukan koreksi agar tercatat di audit
                        log.</li>
                </ul>
            </article>
        </section>
    </main>
</body>

</html>
