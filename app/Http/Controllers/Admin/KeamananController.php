<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class KeamananController extends Controller
{
    public function index()
    {
        wajibSuperadmin();
        $user = session('user');
        $online = DB::table('user_login_statuses as ls')->join('users as u', 'u.id', '=', 'ls.user_id')->where('ls.is_online', 1)->select('ls.*', 'u.nama', 'u.username')->latest('ls.last_seen_at')->get();
        $events = DB::table('login_security_events')->latest('id')->limit(60)->get();
        $inactive = User::where('aktif', 0)->orderBy('role')->orderBy('nama')->get();
        $superadminLogs = DB::table('audit_logs')->where('user_role', 'admin')->latest('id')->limit(40)->get();
        $stats = [
            'online' => $online->count(),
            'failed_today' => DB::table('login_security_events')->whereDate('created_at', now()->toDateString())->count(),
            'suspicious_today' => DB::table('login_security_events')->where('event_type', 'suspicious')->whereDate('created_at', now()->toDateString())->count(),
            'inactive' => $inactive->count(),
        ];

        return view('dashboard.keamanan', compact('user', 'online', 'events', 'inactive', 'superadminLogs', 'stats'));
    }
}
