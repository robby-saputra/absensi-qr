<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #111827;
            margin: 24px
        }

        .kop {
            display: flex;
            align-items: center;
            gap: 14px;
            border-bottom: 3px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 14px
        }

        .kop img {
            width: 72px;
            height: 72px;
            object-fit: contain
        }

        .kop h1 {
            font-size: 21px;
            margin: 0;
            text-transform: uppercase
        }

        .kop p {
            margin: 4px 0 0;
            color: #374151
        }

        .meta {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin: 12px 0 14px;
            font-size: 12px
        }

        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 11px
        }

        th {
            background: #1f2937;
            color: white
        }

        th,
        td {
            border: 1px solid #6b7280;
            padding: 6px;
            vertical-align: top
        }

        .ttd {
            display: flex;
            justify-content: flex-end;
            margin-top: 34px
        }

        .ttd div {
            text-align: center;
            min-width: 220px
        }

        button {
            margin-bottom: 12px;
            padding: 9px 12px;
            border: 0;
            background: #273c75;
            color: white;
            border-radius: 8px;
            font-weight: 700
        }

        @media print {
            button {
                display: none
            }

            body {
                margin: 14px
            }

            .kop img {
                width: 62px;
                height: 62px
            }

            table {
                font-size: 10px
            }
        }
    </style>
</head>

<body>
    <button onclick="window.print()">Cetak / Save as PDF</button>
    <div class="kop">
        <img src="{{ asset(\App\Services\AttendanceSettingService::logoSekolah()) }}" alt="Logo">
        <div>
            <h1>{{ \App\Services\AttendanceSettingService::namaSekolah() }}</h1>
            <p>Dokumen Resmi {{ $title }}</p>
        </div>
    </div>
    <div class="meta">
        <div>{{ $meta ?? '-' }}</div>
        <div>Dicetak: {{ now()->format('d-m-Y H:i') }}</div>
    </div>
    <table>
        <tr>
            <th>No</th>
            @foreach ($headers as $header)
                <th>{{ $header }}</th>
            @endforeach
        </tr>
        @forelse($rows as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                @foreach ($row as $cell)
                    <td>{{ $cell }}</td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($headers) + 1 }}">Data tidak tersedia.</td>
            </tr>
        @endforelse
    </table>
    <div class="ttd">
        <div>
            <p>Mengetahui,</p><br><br><br><strong>Superadmin / Petugas</strong>
        </div>
    </div>
</body>

</html>
