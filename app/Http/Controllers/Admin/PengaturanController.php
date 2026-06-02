<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AttendanceSettingService;

class PengaturanController extends Controller
{
    public function index()
    {
        $user = session('user');
        $settings = AttendanceSettingService::all();

        return view('dashboard.pengaturan', compact('user', 'settings'));
    }
}
