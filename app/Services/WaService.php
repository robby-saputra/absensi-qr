<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WaService
{
    public static function send($number, $message)
    {
        $token = env('FONNTE_TOKEN');

        return Http::withHeaders([
            'Authorization' => $token
        ])->asForm()->post('https://api.fonnte.com/send', [
            'target' => $number,
            'message' => $message,
        ]);
    }
}