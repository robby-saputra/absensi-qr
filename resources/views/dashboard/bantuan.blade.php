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

        .help-pill-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .help-pill {
            display: inline-flex;
            padding: 7px 10px;
            border-radius: 999px;
            background: #e8f1ff;
            color: #24447f;
            font-size: 12px;
            font-weight: 900;
        }

        .help-card h3 { margin: 16px 0 7px; color: #243f7f; font-size: 15px; }
        .help-card code { padding: 2px 6px; border-radius: 5px; background: #eef4ff; color: #24447f; font-weight: 800; }
        .help-steps { counter-reset: helpstep; display: grid; gap: 10px; margin-top: 12px; }
        .help-step { position: relative; padding: 13px 14px 13px 48px; border: 1px solid #e1e8f2; border-radius: 9px; background: #f9fbfe; color: #475569; line-height: 1.55; }
        .help-step:before { counter-increment: helpstep; content: counter(helpstep); position: absolute; left: 13px; top: 12px; display: grid; place-items: center; width: 25px; height: 25px; border-radius: 7px; background: #273c75; color: #fff; font-size: 11px; font-weight: 900; }
        .help-step strong { display: block; margin-bottom: 2px; }
        .help-callout { margin-top: 12px; padding: 13px 15px; border: 1px solid #f1d49c; border-radius: 9px; background: #fff9e9; color: #7a4b05; line-height: 1.55; }
        .help-callout.danger { border-color: #f5c2c7; background: #fff3f3; color: #9f252c; }
        .help-link { display: inline-flex; margin-top: 12px; color: #24447f; font-size: 12px; font-weight: 900; text-decoration: none; }

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

            .help-pill-row {
                display: grid;
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
                <span class="help-kicker">Panduan Sistem Terbaru</span>
                <h1>Pusat Bantuan Absensi QR</h1>
                <p>Halaman ini berisi panduan penggunaan website Laravel terbaru, aplikasi Android Flutter, dashboard
                    mobile, QR absensi, Jam Pelajaran (JP), guru pengganti, pengajuan izin/sakit, dan laporan kehadiran.</p>
                <div class="help-pill-row">
                    <span class="help-pill">Website responsif</span>
                    <span class="help-pill">Tabel jadi card di HP</span>
                    <span class="help-pill">Guru piket per hari</span>
                    <span class="help-pill">Jam kunci absensi</span>
                    <span class="help-pill">Sesi berbasis JP</span>
                    <span class="help-pill">Guru utama & pengganti</span>
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

        <div class="help-note">
            Website digunakan oleh admin, guru, guru piket, wali kelas, dan siswa web. Aplikasi Android digunakan oleh
            siswa dan orang tua. Validasi QR, waktu aktif, dan lokasi absensi tetap ditentukan oleh Laravel/backend.
        </div>

        <section class="help-grid">
            @if ($targetRole !== 'publik')
                <article class="help-card help-wide role-guide">
                    @if ($targetRole === 'admin')
                        <h2>Panduan Utama Admin</h2>
                        <p>Admin mengelola data dasar, jadwal berbasis JP, koreksi absensi, verifikasi izin, laporan, dan arsip. Gunakan urutan kerja berikut agar data antarmodul tetap sinkron.</p>
                        <ul>
                            <li><strong>Siapkan periode:</strong> aktifkan tahun ajaran dan semester yang benar sebelum membuat jadwal atau absensi.</li>
                            <li><strong>Lengkapi data master:</strong> jurusan → kelas → siswa/guru → wali kelas → guru piket.</li>
                            <li><strong>Susun jadwal:</strong> setiap jadwal wajib memiliki hari, kelas, mapel, guru utama, JP mulai, jumlah JP, dan rentang jam.</li>
                            <li><strong>Pantau operasional:</strong> absensi harian berasal dari guru piket; absensi mapel berasal dari sesi QR guru mapel.</li>
                            <li><strong>Proses izin:</strong> persetujuan izin/sakit menyinkronkan status ke absensi harian dan jadwal mapel terkait.</li>
                            <li><strong>Gunakan filter dan ekspor:</strong> pastikan periode/filter benar sebelum Print, Excel, atau PDF.</li>
                        </ul>
                        <div class="help-callout"><strong>Prinsip penting:</strong> absensi harian dan absensi mapel adalah dua sumber berbeda. Jangan memakai jam masuk/pulang sebagai pengganti status kehadiran per JP.</div>
                    @elseif($targetRole === 'piket')
                        <h2>Panduan Guru Piket</h2>
                        <ul>
                            <li>Buka dashboard guru piket untuk melihat jadwal piket, anggota tim, dan QR absensi harian.</li>
                            <li>Generate QR masuk atau pulang sesuai kebutuhan absensi harian.</li>
                            <li>QR harian mewakili tim guru piket pada hari dan jam tugas yang sama.</li>
                            <li>Pantau daftar siswa yang belum masuk, belum pulang, izin, sakit, telat, atau alfa.</li>
                            <li>Pada layar HP, tabel absensi berubah menjadi kartu berlabel agar mudah dicek.</li>
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
                            <li>Gunakan tampilan HP untuk cek cepat karena tabel verifikasi tampil sebagai kartu mobile.</li>
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
                            <li>Di HP, data siswa dan riwayat absensi tampil sebagai kartu agar mudah dibaca.</li>
                            <li>Jika ada data absensi yang perlu dikoreksi setelah Jam Kunci Absensi, hubungi admin sekolah yang berwenang melakukan koreksi.</li>
                        </ul>
                    @elseif($targetRole === 'siswa')
                        <h2>Panduan Siswa</h2>
                        <ul>
                            <li>Login menggunakan akun siswa yang diberikan sekolah.</li>
                            <li>Gunakan aplikasi Android untuk scan QR absensi harian dan absensi mata pelajaran.</li>
                            <li>Aktifkan GPS/lokasi dan izinkan akses lokasi saat scan QR.</li>
                            <li>Gunakan dashboard siswa web untuk melihat riwayat dan mengirim pengajuan sederhana jika tersedia.</li>
                            <li>Ajukan izin/sakit melalui sistem jika tidak dapat hadir.</li>
                            <li>Lihat riwayat absensi dan status pengajuan untuk memastikan data sudah tercatat.</li>
                        </ul>
                    @endif
                </article>

                @if ($targetRole === 'admin')
                    <article class="help-card help-wide">
                        <h2>Urutan Pengaturan Awal Sekolah</h2>
                        <div class="help-steps">
                            <div class="help-step"><strong>1. Tahun Ajaran & Pengaturan Absensi</strong>Aktifkan periode, atur jam masuk, batas terlambat, jam kunci, lokasi sekolah, radius QR, identitas, dan logo sekolah.</div>
                            <div class="help-step"><strong>2. Jurusan dan Kelas</strong>Buat program keahlian, lalu hubungkan setiap kelas ke jurusan. Detail jurusan menampilkan kelas dan siswa aktif.</div>
                            <div class="help-step"><strong>3. Pengguna dan Penugasan</strong>Masukkan siswa/guru, tentukan kelas siswa, wali kelas, tim guru piket, serta guru pengganti bila diperlukan.</div>
                            <div class="help-step"><strong>4. Jadwal Berbasis JP</strong>Pilih kelas, mapel, guru utama, hari, JP mulai, dan jumlah JP. Sistem membentuk label seperti JP 1–2 atau JP 5–6.</div>
                            <div class="help-step"><strong>5. Kalender Sekolah</strong>Isi libur, kegiatan, dan ujian. Hari libur tidak menghasilkan Alfa otomatis. Tampilan kalender dan tabel mengikuti bulan yang dipilih.</div>
                        </div>
                    </article>

                    <article class="help-card">
                        <h2>Jam Pelajaran (JP)</h2>
                        <p>JP adalah identitas sesi belajar, bukan sekadar tulisan jam.</p>
                        <ul>
                            <li><strong>JP mulai</strong> menentukan sesi pertama, misalnya JP 3.</li>
                            <li><strong>Jumlah JP</strong> menentukan panjang sesi. JP mulai 3 dengan jumlah 2 menjadi <strong>JP 3–4</strong>.</li>
                            <li>Rentang jam tetap ditampilkan, misalnya 08:10–09:20.</li>
                            <li>Filter JP membaca rentang; memilih JP 4 akan menemukan jadwal JP 3–4.</li>
                            <li>QR, verifikasi, riwayat, rekap jadwal, dan rekap absensi mapel memakai sesi JP yang sama.</li>
                        </ul>
                        <div class="help-callout danger">Hindari membuat dua jadwal dengan kelas, hari, dan JP yang saling bertabrakan. Gunakan pemeriksaan jadwal bentrok sebelum operasional.</div>
                        <a class="help-link" href="/dashboard/admin/jadwal">Buka Kelola Jadwal →</a>
                    </article>

                    <article class="help-card">
                        <h2>Guru Utama & Guru Pengganti</h2>
                        <ul>
                            <li>Guru utama adalah pemilik jadwal tetap.</li>
                            <li>Jika guru utama izin/sakit, status mengajar harian dapat dialihkan ke guru pengganti.</li>
                            <li>Guru pengganti yang bertugas dapat membuka sesi, memverifikasi absensi, melihat riwayat, dan mengakses detail siswa pada sesi tersebut.</li>
                            <li>Rekap menampilkan guru pelaksana sebenarnya sekaligus informasi guru utama.</li>
                            <li>Hak akses pengganti berlaku pada jadwal/tanggal penugasan, bukan seluruh jadwal guru utama.</li>
                        </ul>
                    </article>

                    <article class="help-card">
                        <h2>Rekap Absensi Harian</h2>
                        <p>Sumber data: guru piket dan QR masuk/pulang sekolah.</p>
                        <ul>
                            <li>Menampilkan jam/status masuk dan jam/status pulang secara terpisah.</li>
                            <li>Status masuk tidak boleh otomatis dianggap sebagai status pulang.</li>
                            <li>Siswa aktif tanpa record pada hari sekolah tampil sebagai Alfa.</li>
                            <li>Record terbaru tampil di atas agar mudah dipantau real-time.</li>
                            <li>Baris virtual Alfa memiliki tombol <strong>Buat Data</strong>; setelah disimpan barulah CRUD dan checkbox hapus aktif.</li>
                            <li>Filter tersedia untuk siswa/NIS, periode, kelas, status, dan tahun ajaran.</li>
                        </ul>
                        <a class="help-link" href="/dashboard/admin/absensi/rekap">Buka Rekap Harian →</a>
                    </article>

                    <article class="help-card">
                        <h2>Rekap Absensi Mapel</h2>
                        <p>Sumber data: jadwal, seluruh siswa aktif di kelas, absensi mapel, dan absensi harian piket.</p>
                        <ul>
                            <li>Semua siswa pada sesi tetap muncul, termasuk yang belum scan.</li>
                            <li>Kolom Absensi Harian Piket membantu membandingkan kehadiran sekolah dengan kehadiran mapel.</li>
                            <li>Status <strong>Belum Absen Mapel</strong> berarti record sesi belum dibuat; ini berbeda dari Alfa yang sudah ditetapkan.</li>
                            <li>Data yang sudah scan ditampilkan paling atas berdasarkan pembaruan terbaru.</li>
                            <li>Gunakan filter tanggal, kelas, mapel, JP, status, tahun ajaran, siswa, atau guru.</li>
                            <li>Tabel dapat digeser horizontal; kolom Aksi berisi Lihat, Ubah, Hapus, atau Buat Data.</li>
                        </ul>
                        <a class="help-link" href="/dashboard/admin/rekap/absensi-mapel">Buka Rekap Mapel →</a>
                    </article>

                    <article class="help-card">
                        <h2>Pengajuan Izin & Sakit</h2>
                        <ul>
                            <li>Pengajuan menunggu tampil paling atas dan dapat difilter berdasarkan siswa, kelas, jenis, status, serta bulan.</li>
                            <li>Periksa alasan, periode, durasi, dan lampiran bukti sebelum mengambil keputusan.</li>
                            <li><strong>Disetujui:</strong> sistem mengisi/memperbarui absensi harian dan absensi mapel terkait, lalu memberi notifikasi kepada guru.</li>
                            <li><strong>Ditolak:</strong> status absensi tidak diubah menjadi izin/sakit.</li>
                            <li>Catatan reviewer, nama reviewer, dan waktu review disimpan sebagai jejak verifikasi.</li>
                        </ul>
                        <a class="help-link" href="/dashboard/admin/pengajuan-izin">Buka Pengajuan Izin →</a>
                    </article>

                    <article class="help-card">
                        <h2>Filter, PDF, Excel & Print</h2>
                        <ul>
                            <li>Terapkan filter dahulu, kemudian periksa jumlah hasil dan periode yang tampil.</li>
                            <li>PDF resmi digunakan untuk dokumen sekolah; Print untuk pencetakan cepat; Excel untuk pengolahan lanjutan.</li>
                            <li>Filter tahun ajaran mencegah data semester berbeda tercampur.</li>
                            <li>Data lama tanpa ID tahun ajaran tetap dicocokkan melalui rentang tanggal periode bila didukung laporan.</li>
                            <li>Jika tabel lebar, gunakan scrollbar horizontal dan petunjuk “Geser tabel”.</li>
                        </ul>
                    </article>

                    <article class="help-card">
                        <h2>CRUD, Hapus Massal & Arsip</h2>
                        <ul>
                            <li><strong>Lihat</strong> membuka detail; <strong>Ubah</strong> memperbaiki record; <strong>Hapus</strong> memindahkan data ke Arsip bila fitur arsip tersedia.</li>
                            <li>Checkbox hanya aktif untuk record yang benar-benar tersimpan. Baris virtual tidak dapat dihapus.</li>
                            <li>Gunakan Hapus Terpilih dengan hati-hati dan periksa jumlah data yang dipilih.</li>
                            <li>Dialog konfirmasi berwarna putih; warna merah hanya menandai ikon dan tombol berbahaya.</li>
                            <li>Data di Arsip dapat diperiksa atau dipulihkan sesuai dukungan jenis datanya.</li>
                        </ul>
                    </article>

                    <article class="help-card">
                        <h2>Kalender & Auto Alfa</h2>
                        <ul>
                            <li>Kalender hanya menampilkan ringkasan dan daftar event pada bulan yang dipilih.</li>
                            <li>Libur dapat bersifat sekolah, provinsi, nasional, atau berulang.</li>
                            <li>Pada hari libur, siswa tanpa scan tidak dihitung sebagai Alfa.</li>
                            <li>Auto Alfa membuat record bagi siswa aktif yang belum memiliki absensi pada hari sekolah.</li>
                            <li>Pastikan tanggal dan tahun ajaran benar sebelum menjalankan proses massal.</li>
                        </ul>
                    </article>

                    <article class="help-card help-wide">
                        <h2>Checklist Operasional Harian Admin</h2>
                        <div class="help-steps">
                            <div class="help-step"><strong>Sebelum sekolah</strong>Periksa kalender libur, jadwal piket, jadwal mapel, status guru, dan guru pengganti.</div>
                            <div class="help-step"><strong>Saat jam masuk</strong>Pantau Rekap Absensi Harian; pastikan scan masuk, telat, izin, sakit, dan siswa tanpa record terbaca benar.</div>
                            <div class="help-step"><strong>Saat pembelajaran</strong>Pantau sesi JP dan Rekap Absensi Mapel; bandingkan dengan absensi harian piket.</div>
                            <div class="help-step"><strong>Sebelum jam kunci</strong>Proses pengajuan izin/sakit dan koreksi data yang jelas keliru.</div>
                            <div class="help-step"><strong>Setelah jam kunci</strong>Lakukan koreksi khusus melalui CRUD admin, lalu ekspor laporan bila diperlukan.</div>
                        </div>
                    </article>
                @endif
            @endif

            <article class="help-card">
                <h2>Tentang Sistem</h2>
                <ul>
                    <li>Sistem ini digunakan untuk absensi siswa berbasis QR Code.</li>
                    <li>Website digunakan oleh admin, guru piket, guru mata pelajaran, wali kelas, dan siswa web.</li>
                    <li>Aplikasi Android digunakan oleh siswa dan orang tua.</li>
                    <li>Website sudah responsif; tabel penting berubah menjadi kartu di layar HP.</li>
                    <li>Radius lokasi absensi default adalah 200 meter dan diatur dari Pengaturan Absensi.</li>
                    <li>Absensi harian dan absensi mata pelajaran terkunci otomatis setelah Jam Kunci Absensi.</li>
                </ul>
            </article>

            <article class="help-card">
                <h2>Panduan Login</h2>
                <ul>
                    <li>Admin, guru piket, guru mata pelajaran, dan wali kelas login melalui website.</li>
                    <li>Siswa dapat memakai aplikasi Android dan dashboard siswa web jika akses web tersedia.</li>
                    <li>Orang tua login melalui aplikasi Android.</li>
                    <li>Jika lupa username atau password, hubungi admin sekolah.</li>
                </ul>
            </article>

            <article class="help-card">
                <h2>Panduan Tampilan HP</h2>
                <ul>
                    <li>Gunakan tombol menu di kiri atas untuk membuka atau menutup sidebar.</li>
                    <li>Pada layar kecil, tabel data otomatis berubah menjadi kartu per baris.</li>
                    <li>Setiap kartu menampilkan label kolom seperti Nama, Kelas, Status, dan Aksi.</li>
                    <li>Tombol aksi dibuat lebih besar agar mudah ditekan di HP.</li>
                    <li>Jika tampilan lama masih muncul, refresh halaman atau bersihkan cache browser.</li>
                </ul>
            </article>

            <article class="help-card">
                <h2>Panduan Guru Piket Per Hari</h2>
                <ul>
                    <li>Admin membuka menu Guru Piket untuk melihat kelompok Senin, Selasa, Rabu, dan seterusnya.</li>
                    <li>Di setiap hari, sistem menampilkan tim berdasarkan jam mulai dan jam selesai.</li>
                    <li>Setiap anggota tim tetap bisa diedit atau dihapus dari kartu anggota.</li>
                    <li>Status tim mengikuti kondisi anggota, misalnya Sedang Bertugas, Akan Bertugas, Selesai, atau Ada Yang Izin/Sakit.</li>
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
