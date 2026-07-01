<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

// Controller kecil ini mengarahkan halaman utama website ke halaman login.
class HomeRedirectController extends Controller
{
    // Saat user membuka root URL, sistem langsung mengarahkan ke /login.
    public function index()
    {
        return redirect('/login');
    }
}
