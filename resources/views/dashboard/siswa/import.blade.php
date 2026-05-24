<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">

<title>Import Siswa</title>

<style>

body{
    font-family:Arial,sans-serif;
    background:#f5f6fa;
    padding:30px;
}

.box{
    max-width:850px;
    margin:auto;
    background:white;
    padding:25px;
    border-radius:12px;
    box-shadow:0 4px 15px rgba(0,0,0,.08);
}

h2{
    margin-top:0;
    color:#273c75;
}

.info{
    background:#eef4ff;
    padding:15px;
    border-radius:8px;
    margin-bottom:20px;
    line-height:1.8;
}

.success{
    background:#dff9fb;
    padding:12px;
    border-radius:8px;
    margin-bottom:15px;
}

.error{
    background:#ffe5e5;
    padding:12px;
    border-radius:8px;
    margin-bottom:15px;
}

input{
    width:100%;
    padding:12px;
    border:1px solid #ddd;
    border-radius:6px;
    margin-top:6px;
    margin-bottom:20px;
    box-sizing:border-box;
}

.btn{
    display:inline-block;
    padding:10px 18px;
    border:none;
    border-radius:6px;
    text-decoration:none;
    cursor:pointer;
    font-size:14px;
}

.import{
    background:#273c75;
    color:white;
}

.back{
    background:#7f8fa6;
    color:white;
}

.template{
    background:#27ae60;
    color:white;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}

table th{
    background:#273c75;
    color:white;
}

table th,
table td{
    border:1px solid #ddd;
    padding:10px;
    text-align:left;
}

code{
    background:#ecf0f1;
    padding:2px 6px;
    border-radius:4px;
}

.note{
    margin-top:15px;
    color:#555;
}

</style>
</head>

<body>

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



@if(session('import_result'))

@php($result = session('import_result'))

<div class="success">

Berhasil import:
<b>{{ $result['success'] }}</b> siswa

<br>

Gagal:
<b>{{ $result['failed'] }}</b> baris

</div>


@if(!empty($result['errors']))

<div class="error">

@foreach($result['errors'] as $error)

<div>
{{ $error }}
</div>

@endforeach

</div>

@endif

@endif




@if($errors->any())

<div class="error">

@foreach($errors->all() as $error)

<div>
{{ $error }}
</div>

@endforeach

</div>

@endif




<form
method="POST"
action="/dashboard/admin/siswa/import"
enctype="multipart/form-data"
>

@csrf


<label>

Upload File Excel / CSV

</label>


<input
type="file"
name="file"
accept=".xlsx,.csv,.txt"
required
>



<a
href="/dashboard/admin/siswa/template"
class="btn template"
>

Download Template

</a>



<a
href="/dashboard/admin/siswa"
class="btn back"
>

Kembali

</a>



<button
type="submit"
class="btn import"
>

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

</body>
</html>