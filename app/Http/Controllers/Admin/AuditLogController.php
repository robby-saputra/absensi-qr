<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user');
        $filters = [
            'q' => $request->get('q', ''),
            'aksi' => $request->get('aksi', ''),
        ];

        $query = DB::table('audit_logs')->latest('id');

        if ($filters['q']) {
            $query->where(function ($search) use ($filters) {
                $search->where('user_name', 'like', '%'.$filters['q'].'%')
                    ->orWhere('aksi', 'like', '%'.$filters['q'].'%')
                    ->orWhere('judul', 'like', '%'.$filters['q'].'%')
                    ->orWhere('tabel', 'like', '%'.$filters['q'].'%');
            });
        }

        if ($filters['aksi']) {
            $query->where('aksi', $filters['aksi']);
        }

        $logs = $query->paginate(20)->withQueryString();
        $aksiList = DB::table('audit_logs')->select('aksi')->distinct()->orderBy('aksi')->pluck('aksi');

        return view('dashboard.audit_log', compact('user', 'logs', 'filters', 'aksiList'));
    }

    public function show($id)
    {
        wajibSuperadmin();

        $user = session('user');
        $log = DB::table('audit_logs')->where('id', $id)->first();
        abort_if(! $log, 404);

        $dataLama = $log->data_lama ? json_decode($log->data_lama, true) : [];
        $dataBaru = $log->data_baru ? json_decode($log->data_baru, true) : [];
        $keys = collect(array_keys($dataLama ?: []))->merge(array_keys($dataBaru ?: []))->unique()->values();

        return view('dashboard.audit_log_detail', compact('user', 'log', 'dataLama', 'dataBaru', 'keys'));
    }
}

