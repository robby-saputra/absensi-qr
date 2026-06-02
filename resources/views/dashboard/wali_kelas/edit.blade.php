<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>

        Edit Wali Kelas

    </title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-wali_kelas-edit.css') }}">

</head>



<body>

    @include('layouts.sidebar_admin')

    <main id="content" class="content">



        <div class="container">


            <h2>

                Edit Wali Kelas

            </h2>



            <a href="/dashboard/admin/wali-kelas" class="btn btn-kembali">

                Kembali

            </a>



            @if (session('error'))
                <div class="alert">

                    {{ session('error') }}

                </div>
            @endif




            <form method="POST" action="/dashboard/admin/wali-kelas/update/{{ $kelas->id }}">

                @csrf



                <label>

                    Kelas

                </label>


                <input type="text" value="{{ $kelas->nama_kelas }}" readonly>




                <label>

                    Pilih Wali Kelas

                </label>



                <select name="wali_kelas_id" required>


                    <option value="">

                        -- Pilih Guru --

                    </option>



                    @foreach ($guru as $g)
                        <option value="{{ $g->id }}"
                            {{ $kelas->wali_kelas_id == $g->id ? 'selected' : '' }}>


                            {{ $g->nama }}

                            ({{ $g->username }})
                        </option>
                    @endforeach


                </select>




                <button type="submit" class="btn btn-update">

                    Update Wali Kelas

                </button>



            </form>


        </div>



    </main>

</body>

</html>
