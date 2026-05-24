<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Siswa</title>

    <style>
        body{
            font-family:Arial, sans-serif;
            background:#f5f6fa;
            padding:30px;
        }

        .box{
            width:600px;
            margin:auto;
            background:white;
            padding:25px;
            border-radius:10px;
            box-shadow:0 2px 8px rgba(0,0,0,0.08);
        }

        h2{
            margin-top:0;
            margin-bottom:20px;
            color:#273c75;
        }

        label{
            font-weight:bold;
            display:block;
            margin-bottom:5px;
        }

        input,
        select{
            width:100%;
            padding:10px;
            margin-bottom:15px;
            border:1px solid #ccc;
            border-radius:5px;
            box-sizing:border-box;
        }

        .readonly{
            background:#ecf0f1;
        }

        .btn-group{
            display:flex;
            gap:10px;
        }

        .btn{
            padding:10px 16px;
            border:none;
            border-radius:5px;
            cursor:pointer;
            text-decoration:none;
            color:white;
            font-size:14px;
        }

        .btn-simpan{
            background:#273c75;
        }

        .btn-simpan:hover{
            background:#192a56;
        }

        .btn-kembali{
            background:#7f8fa6;
        }

        .btn-kembali:hover{
            background:#718093;
        }

        .error{
            background:#e84118;
            color:white;
            padding:10px;
            border-radius:5px;
            margin-bottom:15px;
        }
    </style>
</head>
<body>

<div class="box">

    <h2>Tambah Siswa</h2>

    @if ($errors->any())

        <div class="error">

            <ul style="margin:0; padding-left:20px;">

                @foreach ($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif

    <form method="POST"
          action="/dashboard/admin/siswa/store">

        @csrf

        <!-- NAMA -->
        <label>Nama Siswa</label>

        <input type="text"
               name="nama"
               placeholder="Masukkan nama siswa">

        <!-- NIS -->
        <label>NIS</label>

        <input type="text"
               name="nis"
               placeholder="Masukkan NIS">

        <!-- USERNAME -->
        <label>Username</label>

        <input type="text"
               name="username"
               placeholder="Masukkan username">

        <!-- PASSWORD -->
        <label>Password</label>

        <input type="password"
               name="password"
               placeholder="Masukkan password">

        <!-- JURUSAN -->
        <label>Jurusan</label>

        <select id="jurusan">

            <option value="">
                -- Pilih Jurusan --
            </option>

            @foreach($jurusan as $j)

                <option value="{{ $j->id }}">

                    {{ $j->kode_jurusan }}
                    -
                    {{ $j->nama_jurusan }}

                </option>

            @endforeach

        </select>

        <!-- KELAS -->
        <label>Kelas</label>

        <select name="kelas_id"
                id="kelas_id"
                required>

            <option value="">
                -- Pilih Kelas --
            </option>

            @foreach($kelas as $k)

                <option
                    value="{{ $k->id }}"
                    data-jurusan="{{ $k->jurusan_id }}"
                    data-wali="{{ $k->nama_wali ?? '-' }}">

                    {{ $k->nama_kelas }}

                </option>

            @endforeach

        </select>

        <!-- WALI -->
        <label>Wali Kelas</label>

        <input type="text"
               id="wali_kelas"
               class="readonly"
               placeholder="Otomatis dari kelas"
               readonly>

        <!-- NO ORTU -->
        <label>No Orang Tua</label>

        <input type="text"
               name="no_ortu"
               placeholder="Contoh: 08123456789">

        <div class="btn-group">

            <a href="/dashboard/admin/siswa"
               class="btn btn-kembali">

                Kembali

            </a>

            <button type="submit"
                    class="btn btn-simpan">

                Simpan

            </button>

        </div>

    </form>

</div>

<script>

    const jurusanSelect =
        document.getElementById('jurusan');

    const kelasSelect =
        document.getElementById('kelas_id');

    const waliInput =
        document.getElementById('wali_kelas');

    /*
    |--------------------------------------------------------------------------
    | FILTER KELAS BERDASARKAN JURUSAN
    |--------------------------------------------------------------------------
    */
    jurusanSelect.addEventListener('change', function () {

        const jurusanId = this.value;

        for (let option of kelasSelect.options) {

            if(option.value === '') continue;

            if(option.dataset.jurusan === jurusanId){

                option.style.display = 'block';

            }else{

                option.style.display = 'none';

            }

        }

        kelasSelect.value = '';
        waliInput.value = '';

    });

    /*
    |--------------------------------------------------------------------------
    | AUTO WALI KELAS
    |--------------------------------------------------------------------------
    */
    kelasSelect.addEventListener('change', function () {

        const selected =
            this.options[this.selectedIndex];

        waliInput.value =
            selected.dataset.wali ?? '-';

    });

</script>

</body>
</html>