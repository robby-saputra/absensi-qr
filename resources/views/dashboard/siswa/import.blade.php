<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">

    <title>Import Siswa</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-siswa-import.css') }}">
</head>

<body>

    @include('layouts.sidebar_admin')

    <main id="content" class="content">

        <div class="box">

            <h2>📥 Import Data Siswa</h2>


            <div class="info">

                Format kolom Excel yang dibaca:

                <br><br>

                <code>nis</code>,
                <code>nama</code>,
                <code>username</code>,
                <code>password</code>,
                <code>kelas</code>,
                <code>nama_ortu</code>,
                <code>no_ortu</code>,
                <code>status</code>

                <br><br>

                Kolom:

                <ul>
                    <li><b>kelas</b> → isi nama kelas (contoh: X AK 1)</li>
                    <li><b>status</b> → isi <code>aktif</code> atau <code>nonaktif</code></li>
                    <li>Password kosong → otomatis <code>123456</code></li>
                </ul>

            </div>



            @if (session('import_result'))

                @php($result = session('import_result'))

                <div class="success">

                    Berhasil import:
                    <b>{{ $result['success'] }}</b> siswa

                    <br>

                    Gagal:
                    <b>{{ $result['failed'] }}</b> baris

                </div>


                @if (!empty($result['errors']))

                    <div class="error">

                        @foreach ($result['errors'] as $error)
                            <div>
                                {{ $error }}
                            </div>
                        @endforeach

                    </div>

                @endif

            @endif




            @if ($errors->any())

                <div class="error">

                    @foreach ($errors->all() as $error)
                        <div>
                            {{ $error }}
                        </div>
                    @endforeach

                </div>

            @endif




            <form method="POST" action="/dashboard/admin/siswa/import" enctype="multipart/form-data">

                @csrf


                <label>

                    Upload File Excel / CSV

                </label>


                <input type="file" name="file" accept=".xlsx,.csv,.txt" required>



                <a href="/dashboard/admin/siswa/template" class="btn template">

                    Download Template

                </a>



                <a href="/dashboard/admin/siswa" class="btn back">

                    Kembali

                </a>



                <button type="submit" class="btn import">

                    Import

                </button>


            </form>




            <h3>
                Contoh Format Excel
            </h3>


            <table>

                <tr>

                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Kelas</th>
                    <th>No Ortu</th>
                    <th>Status</th>

                </tr>



                <tr>

                    <td>1001</td>
                    <td>Siswa Contoh</td>
                    <td>siswa1001</td>
                    <td>123456</td>
                    <td>X AK 1</td>
                    <td>08123456789</td>
                    <td>aktif</td>

                </tr>


            </table>



            <div class="note">

                <b>Catatan:</b>

                Jika kolom <code>status</code> kosong,
                otomatis siswa dianggap
                <code>aktif</code>.

            </div>


        </div>

    </main>

</body>

</html>
