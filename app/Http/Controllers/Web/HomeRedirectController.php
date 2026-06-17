<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class HomeRedirectController extends Controller
{
    public function index()
    {
        return redirect('/login');
    }
}
