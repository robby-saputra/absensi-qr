{{-- Pusat Bantuan aplikasi Absensi QR. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Bantuan</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f5f6fa; color: #172033; }
        body.has-sidebar .help-wrap { margin-left: 278px; margin-right: 24px; }
        .help-wrap { max-width: 1160px; margin: 0 auto; padding: 28px; }
        .help-hero { display: flex; justify-content: space-between; gap: 18px; align-items: flex-end; padding: 24px; border-radius: 8px; background: linear-gradient(135deg, #0f2f4a, #0f766e); color: #fff; box-shadow: 0 16px 38px rgba(15, 23, 42, .14); margin-bottom: 18px; }
        .help-hero h1 { margin: 0 0 8px; font-size: 32px; }
        .help-hero p { margin: 0; color: #dbeafe; line-height: 1.6; max-width: 760px; }
        .help-kicker { display: inline-flex; margin-bottom: 9px; color: #bbf7d0; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .help-actions, .help-nav, .help-pill-row { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn, .help-nav a { display: inline-flex; min-height: 40px; align-items: center; justify-content: center; padding: 9px 13px; border-radius: 8px; background: #fff; color: #172033; text-decoration: none; font-weight: 800; border: 1px solid #e2e8f0; }
        .help-nav { margin-bottom: 18px; }
        .help-nav a { background: #eef6ff; color: #0f3c68; font-size: 13px; }
        .help-note { padding: 14px 16px; border-left: 5px solid #0f766e; border-radius: 8px; background: #ecfdf3; color: #166534; font-weight: 700; line-height: 1.55; margin-bottom: 18px; }
        .help-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 14px; }
        .help-card { background: #fff; border: 1px solid #e5eaf2; border-radius: 8px; padding: 18px; box-shadow: 0 12px 28px rgba(15, 23, 42, .06); }
        .help-card h2 { margin: 0 0 10px; color: #111827; font-size: 20px; }
        .help-card h3 { margin: 16px 0 7px; color: #0f3c68; font-size: 15px; }
        .help-card p { margin: 0 0 12px; color: #64748b; line-height: 1.55; }
        .help-card ul, .help-card ol { margin: 0; padding-left: 18px; color: #334155; line-height: 1.75; }
        .help-card li + li { margin-top: 4px; }
        .help-card strong { color: #172033; }
        .help-card code { padding: 2px 6px; border-radius: 5px; background: #eef4ff; color: #24447f; font-weight: 800; }
        .help-wide { grid-column: 1 / -1; }
        .role-guide { border-left: 5px solid #0f766e; }
        .help-pill { display: inline-flex; padding: 7px 10px; border-radius: 999px; background: #e8f1ff; color: #24447f; font-size: 12px; font-weight: 900; }
        .help-callout { margin-top: 12px; padding: 13px 15px; border: 1px solid #f1d49c; border-radius: 8px; background: #fff9e9; color: #7a4b05; line-height: 1.55; }
        .help-callout.danger { border-color: #f5c2c7; background: #fff3f3; color: #9f252c; }
        .help-link { display: inline-flex; margin-top: 12px; color: #0f3c68; font-size: 12px; font-weight: 900; text-decoration: none; }
        .faq details { border-top: 1px solid #e5e7eb; padding: 11px 0; }
        .faq summary { cursor: pointer; font-weight: 800; color: #172033; }
        .faq p { margin-top: 8px; }
        @media(max-width:760px) {
            .help-wrap { margin-left: 0 !important; margin-right: 0 !important; padding: 18px; }
            .help-hero { display: grid; align-items: flex-start; }
            .help-actions .btn, .help-nav a { width: 100%; }
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
                <span class="help-kicker">Panduan Sistem Terbaru</span>
                <h1>Pusat Bantuan Absensi QR</h1>
                <p>Panduan ini menjelaskan alur absensi harian, absensi mata pelajaran, status guru utama, guru pengganti, Monitoring Verifikasi Guru, QR, pengajuan izin, laporan, dan pemecahan masalah.</p>
                <div class="help-pill-row">
                    <span class="help-pill">Hadir Manual</span>
                    <span class="help-pill">Hadir Otomatis</span>
                    <span class="help-pill">Bypass Cutoff</span>
                    <span class="help-pill">Guru Pengganti</span>
                    <span class="help-pill">QR Aktif</span>
                    <span class="help-pill">Audit Log</span>
                </div>
            </div>
            <div class="help-actions">
                <a class="btn" href="{{ $backUrl }}">Kembali</a>
                @if (($role ?? '') !== 'publik')
                    <a class="btn" href="/dashboard/bantuan?context={{ $targetRole }}">Panduan Role Ini</a>
                @endif
                @if (($role ?? '') === 'publik')
                    <a class="btn" href="/login">Masuk Sistem</a>
                @endif
            </div>
        </section>

        <nav class="help-nav" aria-label="Navigasi pusat bantuan">
            <a href="#tentang">Tentang Sistem</a>
            <a href="#admin">Panduan Admin</a>
            <a href="#piket">Guru Piket</a>
            <a href="#mapel">Guru Mapel</a>
            <a href="#pengganti">Guru Pengganti</a>
            <a href="#status-guru">Status Guru</a>
            <a href="#monitoring">Monitoring Verifikasi</a>
            <a href="#batal">Batalkan Verifikasi</a>
            <a href="#qr">QR</a>
            <a href="#faq">FAQ</a>
            <a href="#troubleshooting">Troubleshooting</a>
        </nav>

        <div class="help-note">
            Cutoff guru tidak dihapus secara global. Bypass setelah reset admin hanya berlaku untuk satu guru, satu tanggal, satu jadwal atau tugas, dan satu kali verifikasi ulang.
        </div>

        <section class="help-grid">
            <article id="tentang" class="help-card help-wide">
                <h2>1. Tentang Sistem</h2>
                <p>Absensi QR adalah sistem absensi sekolah berbasis Laravel, QR Code, GPS/geofencing, aplikasi Android Flutter, dan notifikasi Firebase Cloud Messaging.</p>
                <ul>
                    <li>Absensi harian mencatat scan masuk dan pulang siswa.</li>
                    <li>Absensi mata pelajaran mencatat kehadiran siswa per jadwal dan Jam Pelajaran (JP).</li>
                    <li>Backend Laravel memvalidasi QR, waktu aktif, lokasi, radius, status guru aktif, dan duplikasi scan.</li>
                    <li>Data izin/sakit siswa, rekap, laporan, arsip, dan notifikasi dikelola secara terpusat.</li>
                </ul>
            </article>

            <article id="admin" class="help-card role-guide">
                <h2>2. Panduan Admin</h2>
                <ul>
                    <li>Kelola siswa, guru, kelas, jurusan, wali kelas, guru piket, jadwal mapel, kalender sekolah, dan pengaturan absensi.</li>
                    <li>Pantau Dashboard Admin: siswa aktif, guru aktif, kelas, jurusan, orang tua aktif, QR aktif, ringkasan operasional, panel Perlu Perhatian, quick actions, notifikasi, dan distribusi siswa per kelas.</li>
                    <li>Gunakan Monitoring Verifikasi Guru untuk mengecek status guru piket dan guru mata pelajaran per tanggal.</li>
                    <li>Proses pengajuan izin/sakit siswa dan ekspor laporan harian atau mapel sesuai filter.</li>
                    <li>Gunakan Arsip untuk memeriksa atau memulihkan data yang didukung fitur arsip.</li>
                </ul>
            </article>

            <article id="piket" class="help-card">
                <h2>3. Panduan Guru Piket</h2>
                <ul>
                    <li>Guru piket utama memilih Hadir, Izin, atau Sakit sebelum cutoff normal pukul 07.00 WIB.</li>
                    <li>Jika tidak memilih sampai cutoff, status efektif guru utama dapat menjadi Hadir Otomatis.</li>
                    <li>Guru piket aktif dapat mengelola QR masuk dan QR pulang sesuai jam tugas.</li>
                    <li>Jika guru utama izin/sakit, guru pengganti harus melakukan konfirmasi sendiri sebelum menjadi petugas aktif.</li>
                    <li>Koreksi absensi siswa mengikuti Jam Kunci Absensi; setelah terkunci, koreksi dilakukan admin.</li>
                </ul>
            </article>

            <article id="mapel" class="help-card">
                <h2>4. Panduan Guru Mata Pelajaran</h2>
                <ul>
                    <li>Guru mapel utama memilih Hadir, Izin, atau Sakit sebelum cutoff normal pukul 06.30 WIB atau nilai setting yang berlaku.</li>
                    <li>Jika tidak memilih sampai cutoff, status efektif guru utama dapat menjadi Hadir Otomatis dan tidak dihitung sebagai belum verifikasi.</li>
                    <li>Guru aktif dapat memulai sesi mapel dan menampilkan QR mata pelajaran.</li>
                    <li>Guru pengganti mapel belum menjadi guru aktif sebelum memilih Hadir.</li>
                    <li>Absensi mapel direkap berdasarkan jadwal, kelas, mapel, guru aktif, dan JP.</li>
                </ul>
            </article>

            <article id="wali" class="help-card">
                <h2>5. Panduan Wali Kelas</h2>
                <ul>
                    <li>Pantau ringkasan kehadiran siswa di kelas wali.</li>
                    <li>Lihat detail siswa, riwayat absensi harian, dan laporan bulanan.</li>
                    <li>Gunakan data izin, sakit, telat, dan alfa untuk tindak lanjut pembinaan.</li>
                    <li>Jika data perlu dikoreksi setelah jam kunci, hubungi admin sekolah.</li>
                </ul>
            </article>

            <article id="siswa" class="help-card">
                <h2>6. Panduan Siswa</h2>
                <ul>
                    <li>Login melalui aplikasi Android atau dashboard siswa web jika tersedia.</li>
                    <li>Aktifkan GPS dan izinkan akses lokasi saat scan QR.</li>
                    <li>Scan QR masuk, QR mapel, dan QR pulang sesuai jadwal.</li>
                    <li>Ajukan izin/sakit melalui aplikasi bila tidak dapat hadir.</li>
                    <li>Periksa riwayat absensi untuk memastikan data sudah tercatat.</li>
                </ul>
            </article>

            <article id="orang-tua" class="help-card">
                <h2>7. Panduan Orang Tua</h2>
                <ul>
                    <li>Login melalui aplikasi Android orang tua.</li>
                    <li>Pantau dashboard dan riwayat kehadiran anak.</li>
                    <li>Terima notifikasi Firebase Cloud Messaging saat ada informasi kehadiran yang dikirim sistem.</li>
                    <li>Hubungi sekolah jika data kehadiran anak tidak sesuai.</li>
                </ul>
            </article>

            <article id="status-guru" class="help-card help-wide">
                <h2>8. Status Kehadiran Guru</h2>
                <h3>Guru utama</h3>
                <ul>
                    <li><strong>Belum Konfirmasi:</strong> guru belum memilih status sebelum cutoff.</li>
                    <li><strong>Hadir Manual:</strong> guru memilih Hadir sendiri.</li>
                    <li><strong>Hadir Otomatis:</strong> guru utama belum memilih sampai cutoff dan sistem menilai guru hadir efektif.</li>
                    <li><strong>Izin/Sakit:</strong> guru utama berhalangan dan proses pengganti dapat dimulai.</li>
                    <li><strong>Digantikan:</strong> tugas dialihkan sesuai rantai pengganti.</li>
                    <li><strong>Menunggu Verifikasi Ulang:</strong> status dibatalkan admin dan guru mendapat bypass satu kali.</li>
                </ul>
                <h3>Guru pengganti</h3>
                <ul>
                    <li>Pengganti tidak pernah menjadi Hadir Otomatis.</li>
                    <li>Status awal setelah ditunjuk adalah Menunggu Konfirmasi.</li>
                    <li>Pengganti memilih Hadir, Izin, atau Sakit secara eksplisit.</li>
                    <li>Pengganti baru mendapat akses QR setelah memilih Hadir.</li>
                    <li>Jika pengganti memilih Izin/Sakit, admin dapat menunjuk Pengganti Lanjutan dan pengganti lanjutan juga wajib konfirmasi sendiri.</li>
                </ul>
            </article>

            <article id="monitoring" class="help-card help-wide">
                <h2>9. Monitoring Verifikasi Guru</h2>
                <p>Admin membuka Monitoring Verifikasi Guru untuk memeriksa sinkronisasi status guru piket dan guru mata pelajaran.</p>
                <ul>
                    <li>Pilih tanggal tugas dan tab Guru Piket atau Guru Mata Pelajaran.</li>
                    <li>Lihat status efektif, sumber status manual/otomatis, guru cadangan, pengganti aktif, pengganti lanjutan, petugas aktif atau guru aktif, dan detail absensi siswa.</li>
                    <li>Tombol Batalkan Verifikasi muncul untuk status yang dapat dikoreksi seperti Hadir Manual, Hadir Otomatis, Izin, Sakit, atau Digantikan.</li>
                    <li>Tombol tidak muncul untuk status yang masih Belum Konfirmasi sebelum cutoff atau sudah Menunggu Verifikasi Ulang.</li>
                    <li>Gunakan halaman ini jika status antarhalaman terlihat berbeda, karena halaman ini memakai status efektif dari service pusat.</li>
                </ul>
            </article>

            <article id="batal" class="help-card">
                <h2>10. Batalkan Verifikasi Guru Utama</h2>
                <ol>
                    <li>Admin membuka Monitoring Verifikasi Guru.</li>
                    <li>Admin memilih Batalkan Verifikasi.</li>
                    <li>Admin wajib mengisi alasan.</li>
                    <li>Status berubah menjadi Menunggu Verifikasi Ulang.</li>
                    <li>Guru mendapat bypass cutoff khusus.</li>
                    <li>Form verifikasi tampil kembali walau batas waktu sudah lewat.</li>
                    <li>Guru memilih Hadir, Izin, atau Sakit.</li>
                    <li>Setelah status baru tersimpan, bypass berakhir dan form terkunci kembali.</li>
                    <li>Perubahan dicatat dalam audit log.</li>
                </ol>
            </article>

            <article id="bypass" class="help-card">
                <h2>11. Verifikasi Ulang dan Bypass Cutoff</h2>
                <ul>
                    <li>Bypass hanya berlaku untuk satu guru, satu tanggal, satu jadwal atau tugas, dan satu kali verifikasi ulang.</li>
                    <li>Cutoff 07.00 untuk guru piket utama dan cutoff 06.30 atau setting untuk guru mapel utama tetap berlaku untuk guru lain.</li>
                    <li>Status Menunggu Verifikasi Ulang memiliki prioritas lebih tinggi daripada Hadir Otomatis.</li>
                    <li>Setelah guru memilih status baru, sumber status dicatat sebagai manual setelah reset admin atau nilai setara.</li>
                </ul>
            </article>

            <article id="pengganti" class="help-card help-wide">
                <h2>12. Guru Pengganti dan Pengganti Lanjutan</h2>
                <ul>
                    <li>Pengganti pertama ditunjuk saat guru utama Izin atau Sakit.</li>
                    <li>Status awal pengganti adalah Menunggu Konfirmasi.</li>
                    <li>Jika pengganti memilih Hadir, pengganti menjadi petugas/guru aktif.</li>
                    <li>Jika pengganti memilih Izin atau Sakit, penugasan ditandai berhalangan dan admin dapat menunjuk pengganti lanjutan.</li>
                    <li>Pengganti lanjutan harus melakukan konfirmasi sendiri. Konfirmasi lama tidak dipakai ulang.</li>
                    <li>Hanya pengganti terakhir yang Hadir yang menjadi petugas aktif.</li>
                    <li>Admin dapat membatalkan verifikasi pengganti yang sudah memilih status; status menjadi Menunggu Verifikasi Ulang Pengganti, QR dicabut, dan pengganti mendapat bypass satu kali.</li>
                </ul>
            </article>

            <article id="qr" class="help-card">
                <h2>13. QR Masuk, Mapel, dan Pulang</h2>
                <ul>
                    <li>QR masuk dan pulang hanya dapat dikelola guru piket aktif atau operator piket sesuai izin sistem.</li>
                    <li>QR mata pelajaran hanya dapat dibuat oleh guru mapel aktif.</li>
                    <li>Pengganti belum aktif sebelum memilih Hadir, sehingga QR tetap dinonaktifkan.</li>
                    <li>Pengganti lama yang dibatalkan atau digantikan tidak boleh memakai akses QR.</li>
                    <li>Jika guru utama reset lalu memilih Hadir, pengganti lama tetap nonaktif.</li>
                </ul>
            </article>

            <article id="izin" class="help-card">
                <h2>14. Pengajuan Izin atau Sakit</h2>
                <ul>
                    <li>Siswa mengajukan izin/sakit melalui aplikasi.</li>
                    <li>Admin memeriksa alasan, tanggal, durasi, dan lampiran bila ada.</li>
                    <li>Jika disetujui, sistem menyinkronkan absensi harian dan mapel terkait.</li>
                    <li>Jika ditolak, status absensi tidak diubah menjadi izin/sakit.</li>
                </ul>
            </article>

            <article id="laporan" class="help-card">
                <h2>15. Rekap dan Laporan</h2>
                <ul>
                    <li>Rekap harian memakai data scan masuk/pulang dan status harian siswa.</li>
                    <li>Rekap mapel memakai jadwal, JP, guru aktif, kelas, dan absensi mapel.</li>
                    <li>Gunakan filter tanggal, kelas, mapel, status, siswa, guru, dan tahun ajaran sebelum ekspor.</li>
                    <li>Print, Excel, dan PDF digunakan sesuai kebutuhan laporan sekolah.</li>
                </ul>
            </article>

            <article id="notifikasi" class="help-card">
                <h2>16. Notifikasi</h2>
                <ul>
                    <li>Notifikasi web digunakan untuk informasi operasional admin, guru, dan siswa.</li>
                    <li>Firebase Cloud Messaging digunakan aplikasi Android untuk siswa dan orang tua.</li>
                    <li>Token perangkat perlu terdaftar agar notifikasi mobile dapat terkirim.</li>
                </ul>
            </article>

            <article class="help-card help-wide">
                <h2>17. Perlindungan Data Absensi Siswa</h2>
                <ul>
                    <li>Batalkan Verifikasi tidak menghapus absensi siswa.</li>
                    <li>Histori scan dan histori QR tidak dihapus.</li>
                    <li>Yang diperbarui adalah status guru, status pengganti, hak akses QR, dan audit log.</li>
                    <li>Jika QR dicabut, QR lama dinonaktifkan untuk mencegah dua guru aktif bersamaan.</li>
                </ul>
            </article>

            <article id="troubleshooting" class="help-card help-wide">
                <h2>18. Pemecahan Masalah</h2>
                <ul>
                    <li><strong>Status masih Belum Konfirmasi:</strong> guru belum memilih status atau tanggal belum melewati cutoff.</li>
                    <li><strong>Status Hadir Otomatis:</strong> guru utama belum memilih status sampai cutoff; ini tidak berlaku untuk guru pengganti.</li>
                    <li><strong>Form verifikasi tidak muncul:</strong> status sudah dipilih, jadwal tidak sesuai tanggal, atau akun bukan guru yang terkait.</li>
                    <li><strong>Form muncul setelah reset admin:</strong> itu bypass khusus untuk satu kali verifikasi ulang.</li>
                    <li><strong>QR dinonaktifkan:</strong> belum ada petugas/guru aktif, pengganti belum memilih Hadir, jadwal belum mulai, jadwal sudah selesai, atau QR lama dicabut.</li>
                    <li><strong>Pengganti belum aktif:</strong> pengganti masih Menunggu Konfirmasi atau memilih Izin/Sakit.</li>
                    <li><strong>Status tidak sinkron:</strong> buka Monitoring Verifikasi Guru dan bersihkan cache aplikasi.</li>
                    <li><strong>Error 403:</strong> role atau akun tidak memiliki akses ke halaman/tugas tersebut.</li>
                    <li><strong>Error 500:</strong> laporkan ke administrator teknis dan periksa log server tanpa membagikan kredensial.</li>
                    <li><strong>CSS hosting belum berubah:</strong> pastikan asset publik tersalin ke document root hosting dan cache browser dibersihkan.</li>
                    <li><strong>Cache Laravel:</strong> administrator teknis dapat menjalankan <code>php artisan optimize:clear</code>.</li>
                    <li><strong>Migration pending:</strong> cek dengan <code>php artisan migrate:status</code>, lalu jalankan migration terencana bila aman.</li>
                    <li><strong>Cek route:</strong> gunakan <code>php artisan route:list</code> untuk memastikan route tersedia.</li>
                </ul>
                <div class="help-callout danger">Jangan gunakan perintah penghapus database seperti migrate:fresh, migrate:refresh, atau db:wipe pada data kerja sekolah.</div>
            </article>

            <article id="faq" class="help-card help-wide faq">
                <h2>19. FAQ</h2>
                @php
                    $faqs = [
                        'Mengapa guru berstatus Hadir Otomatis?' => 'Guru utama belum memilih status sampai cutoff, sehingga sistem menghitung status efektif sebagai Hadir Otomatis.',
                        'Apa perbedaan Hadir Manual dan Hadir Otomatis?' => 'Hadir Manual dipilih langsung oleh guru. Hadir Otomatis dihitung sistem setelah cutoff untuk guru utama yang tidak memilih status.',
                        'Mengapa guru pengganti masih Menunggu Konfirmasi?' => 'Pengganti baru ditunjuk dan belum memilih Hadir, Izin, atau Sakit.',
                        'Apakah guru pengganti bisa Hadir Otomatis?' => 'Tidak. Guru pengganti wajib melakukan konfirmasi sendiri.',
                        'Kapan QR guru pengganti aktif?' => 'QR aktif setelah pengganti memilih Hadir dan menjadi petugas atau guru aktif.',
                        'Apa yang terjadi jika guru pengganti memilih Izin atau Sakit?' => 'Penugasan ditandai berhalangan dan admin dapat menunjuk pengganti lanjutan.',
                        'Bagaimana admin membatalkan verifikasi guru?' => 'Admin membuka Monitoring Verifikasi Guru, memilih Batalkan Verifikasi, mengisi alasan, lalu sistem mengubah status menjadi Menunggu Verifikasi Ulang.',
                        'Apakah guru masih bisa verifikasi setelah cutoff?' => 'Bisa hanya jika admin membatalkan verifikasi dan bypass khusus masih aktif.',
                        'Apakah cutoff pukul 07.00 dan 06.30 dihapus?' => 'Tidak. Cutoff tetap berlaku normal untuk guru lain.',
                        'Mengapa status menjadi Menunggu Verifikasi Ulang?' => 'Status lama dibatalkan admin dan guru perlu memilih ulang.',
                        'Berapa kali bypass dapat digunakan?' => 'Satu kali untuk guru, tanggal, dan jadwal atau tugas yang direset.',
                        'Apakah pembatalan verifikasi menghapus absensi siswa?' => 'Tidak. Data absensi, histori scan, dan histori QR tidak dihapus.',
                        'Mengapa tombol Batalkan Verifikasi tidak muncul?' => 'Status masih Belum Konfirmasi, sudah Menunggu Verifikasi Ulang, jadwal tidak aktif, atau user bukan admin.',
                        'Mengapa guru belum dapat membuka QR?' => 'Guru belum menjadi petugas/guru aktif, pengganti belum konfirmasi Hadir, atau QR berada di luar waktu/jadwal yang valid.',
                        'Siapa yang menjadi petugas aktif jika ada pengganti lanjutan?' => 'Pengganti terakhir yang memilih Hadir menjadi petugas aktif.',
                        'Apa yang harus dilakukan jika status antarlaman berbeda?' => 'Gunakan Monitoring Verifikasi Guru sebagai acuan dan jalankan optimize:clear jika cache aplikasi belum bersih.',
                        'Bagaimana memuat ulang cache jika tampilan hosting belum berubah?' => 'Administrator teknis dapat menjalankan php artisan optimize:clear dan memastikan asset public sudah tersalin ke public_html.',
                    ];
                @endphp
                @foreach ($faqs as $question => $answer)
                    <details>
                        <summary>{{ $question }}</summary>
                        <p>{{ $answer }}</p>
                    </details>
                @endforeach
            </article>

            <article class="help-card help-wide">
                <h2>Kontak Bantuan</h2>
                <p>Untuk kendala akun, data absensi, status guru, QR, atau laporan, hubungi admin sekolah. Jangan mengirim password, token, atau kredensial hosting melalui pesan publik.</p>
            </article>
        </section>
    </main>
</body>

</html>
