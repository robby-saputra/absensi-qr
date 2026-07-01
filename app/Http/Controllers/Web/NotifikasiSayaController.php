<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

// Controller ini menampilkan notifikasi milik user yang sedang login.
class NotifikasiSayaController extends Controller
{
    // Mengambil notifikasi pribadi lalu menandai notifikasi belum dibaca menjadi dibaca.
    public function index()
    {
        $user = session('user');
        // Hanya notifikasi dengan user_id yang sama yang boleh ditampilkan.
        $items = DB::table('notifications')
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(80)
            ->get();

        // Setelah halaman dibuka, status notifikasi user diubah menjadi dibaca.
        DB::table('notifications')->where('user_id', $user->id)->where('status', 'belum_dibaca')->update(['status' => 'dibaca', 'updated_at' => now()]);

        return view('dashboard.role_notifications', compact('user', 'items'));
    }
}
