<?php

use App\Support\AbsensiRekapSync;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('absensi:sinkron-rekap {--tanggal=}', function () {
    $result = AbsensiRekapSync::harian($this->option('tanggal') ?: null);
    $this->info($result['message']);
})->purpose('Sinkronkan absensi harian agar masuk ke rekap admin');

Schedule::command('absensi:sinkron-rekap')->dailyAt('23:55');
