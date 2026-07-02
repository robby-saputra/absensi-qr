{{-- File ini menampilkan halaman import siswa untuk memasukkan banyak data siswa sekaligus dari file. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Template Siswa</title>
</head>

<body>
    @include('layouts.sidebar_admin')
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-siswa-import.css') }}?v={{ filemtime(public_path('css/pages/dashboard-siswa-import.css')) }}">

    <main id="content" class="content">
        <div class="student-import-page">
            <section class="import-hero">
                <div>
                    <span>Template Data Siswa</span>
                    <h1>Upload Template Siswa</h1>
                    <p>Gunakan template resmi agar data siswa, kelas, dan kontak orang tua dapat diimpor dengan rapi.</p>
                </div>
                <div class="hero-actions">
                    <a href="/dashboard/admin/siswa" class="btn ghost">Kembali</a>
                    <a href="/dashboard/admin/siswa/template" class="btn light">Download Template</a>
                </div>
            </section>

            @if (session('import_result'))
                @php($result = session('import_result'))
                <div class="import-result success">
                    Berhasil import: <b>{{ $result['success'] }}</b> siswa<br>
                    Gagal: <b>{{ $result['failed'] }}</b> baris
                </div>

                @if (!empty($result['errors']))
                    <div class="import-result error">
                        @foreach ($result['errors'] as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
            @endif

            @if ($errors->any())
                <div class="import-result error">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <div class="import-layout">
                <aside class="format-card">
                    <div class="format-icon">XL</div>
                    <span>Format Template</span>
                    <h2>Kolom yang Dibaca Sistem</h2>
                    <p>Pastikan nama kolom tidak diubah agar proses import dapat membaca data dengan benar.</p>

                    <div class="column-list">
                        <code>nis</code>
                        <code>nama</code>
                        <code>username</code>
                        <code>password</code>
                        <code>kelas</code>
                        <code>nama_ortu</code>
                        <code>no_ortu</code>
                        <code>status</code>
                    </div>

                    <div class="note">
                        <strong>Catatan</strong>
                        <span>Kolom <code>kelas</code> diisi nama kelas seperti <code>X AK 1</code>. Status diisi <code>aktif</code> atau <code>nonaktif</code>. Password kosong otomatis menjadi <code>123456</code>.</span>
                    </div>
                </aside>

                <section class="upload-card">
                    <header>
                        <span>Upload File</span>
                        <h2>Import Data Siswa</h2>
                        <p>Pilih file template yang sudah diisi, lalu klik Import.</p>
                    </header>

                    <form method="POST" action="/dashboard/admin/siswa/import" enctype="multipart/form-data">
                        @csrf

                        <label class="upload-field">
                            <span>File Excel / CSV <b>*</b></span>
                            <input type="file" name="file" accept=".xlsx,.csv,.txt" required>
                            <small>Format yang diterima: .xlsx, .csv, atau .txt.</small>
                        </label>

                        <footer class="form-actions">
                            <a href="/dashboard/admin/siswa/template" class="btn template">Download Template</a>
                            <button type="submit" class="btn import">Import Siswa</button>
                        </footer>
                    </form>
                </section>
            </div>

            <section class="preview-card">
                <header>
                    <span>Contoh Format Excel</span>
                    <h2>Isi Template Seperti Contoh Ini</h2>
                </header>

                <table>
                    <tr>
                        <th>NIS</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Kelas</th>
                        <th>Nama Ortu</th>
                        <th>No Ortu</th>
                        <th>Status</th>
                    </tr>
                    <tr>
                        <td>1001</td>
                        <td>Siswa Contoh</td>
                        <td>siswa1001</td>
                        <td>123456</td>
                        <td>X AK 1</td>
                        <td>Orang Tua Siswa</td>
                        <td>08123456789</td>
                        <td>aktif</td>
                    </tr>
                </table>
            </section>
        </div>
    </main>
</body>

</html>
