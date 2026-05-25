<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Nilai</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-nilai-edit.css') }}">
</head>
<body>

@include('layouts.sidebar_admin')

<main id="content" class="content">

<div class="box">

    <h2>Edit Nilai</h2>

    <form method="POST"
          action="/dashboard/admin/nilai/update/{{ $nilai->id }}">

        @csrf

        <label>Nilai</label>
        <input type="number"
               name="nilai"
               value="{{ $nilai->nilai }}">

        <button type="submit">Update</button>

    </form>

</div>

</main>

</body>
</html>




