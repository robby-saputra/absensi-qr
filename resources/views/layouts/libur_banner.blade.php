@if (($infoLiburHariIni ?? collect())->isNotEmpty())
    <div class="holiday-banner">
        <div class="holiday-banner-icon"><i class="fa-solid fa-calendar-day"></i></div>
        <div>
            <strong>Hari Ini Libur</strong>
            @foreach ($infoLiburHariIni as $info)
                <p>{{ $info->judul }}{{ $info->keterangan ? ' - ' . $info->keterangan : '' }}</p>
            @endforeach
        </div>
    </div>
@endif
