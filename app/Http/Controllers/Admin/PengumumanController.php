<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PengumumanController extends Controller
{
    public function index()
    {
        wajibSuperadmin();
        $user = session('user');
        $pengumuman = DB::table('announcements as a')->leftJoin('users as u', 'u.id', '=', 'a.created_by')->whereNull('a.deleted_at')->select('a.*', 'u.nama as pembuat')->latest('a.id')->get();

        return view('dashboard.pengumuman.index', compact('user', 'pengumuman'));
    }

    public function create()
    {
        wajibSuperadmin();
        $user = session('user');
        $item = null;
        $mode = 'create';

        return view('dashboard.pengumuman.form', compact('user', 'item', 'mode'));
    }

    public function edit($id)
    {
        wajibSuperadmin();
        $user = session('user');
        $item = DB::table('announcements')->where('id', $id)->first();
        abort_if(! $item, 404);
        $mode = 'edit';

        return view('dashboard.pengumuman.form', compact('user', 'item', 'mode'));
    }
}
