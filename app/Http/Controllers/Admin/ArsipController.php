<?php

namespace App\Http\Controllers\Admin;

use App\Support\AuditLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

class ArsipController extends Controller
{
    public function index(Request $request)
    {
        wajibSuperadmin();

        $user = session('user');
        $table = $request->get('table', 'users');
        $tanggalMulai = $request->get('tanggal_mulai');
        $tanggalSelesai = $request->get('tanggal_selesai');
        $deletedBy = $request->get('deleted_by');
        abort_if(! array_key_exists($table, tabelBisaArsip()), 404);

        $data = collect();
        if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
            $query = DB::table($table)->whereNotNull('deleted_at');
            if ($tanggalMulai) {
                $query->whereDate('deleted_at', '>=', $tanggalMulai);
            }
            if ($tanggalSelesai) {
                $query->whereDate('deleted_at', '<=', $tanggalSelesai);
            }
            if ($deletedBy) {
                $ids = DB::table('audit_logs')->where('aksi', 'soft_delete')->where('tabel', $table)->where('user_name', 'like', '%'.$deletedBy.'%')->pluck('record_id');
                $query->whereIn('id', $ids);
            }
            $data = $query->latest('deleted_at')->paginate(25)->withQueryString();
        }

        $tables = tabelBisaArsip();
        $filters = compact('tanggalMulai', 'tanggalSelesai', 'deletedBy');

        return view('dashboard.arsip.index', compact('user', 'tables', 'table', 'data', 'filters'));
    }

    public function preview(Request $request)
    {
        wajibSuperadmin();
        $request->validate(['table' => 'required|string', 'id' => 'required|integer']);
        abort_if(! array_key_exists($request->table, tabelBisaArsip()), 404);
        $row = DB::table($request->table)->where('id', $request->id)->first();
        abort_if(! $row, 404);
        $audit = DB::table('audit_logs')->where('aksi', 'soft_delete')->where('tabel', $request->table)->where('record_id', $request->id)->latest('id')->first();
        $user = session('user');

        return view('dashboard.arsip.preview', compact('user', 'row', 'audit') + ['table' => $request->table]);
    }

    public function restore(Request $request)
    {
        wajibSuperadmin();

        $request->validate([
            'table' => 'required|string',
            'id' => 'required|integer',
        ]);
        abort_if(! array_key_exists($request->table, tabelBisaArsip()), 404);

        $before = DB::table($request->table)->where('id', $request->id)->first();
        abort_if(! $before, 404);

        $payload = ['deleted_at' => null];
        if (Schema::hasColumn($request->table, 'updated_at')) {
            $payload['updated_at'] = now();
        }
        try {
            DB::table($request->table)->where('id', $request->id)->update($payload);
            AuditLogger::record('restore', $request->table, (int) $request->id, 'Data dipulihkan dari arsip', $before, DB::table($request->table)->where('id', $request->id)->first(), $request);
        } catch (QueryException $exception) {
            report($exception);

            return back()->with('error', 'Data arsip gagal dipulihkan karena bentrok dengan data aktif.');
        }

        return back()->with('success', 'Data berhasil dipulihkan dari arsip.');
    }

    public function bulkRestore(Request $request)
    {
        wajibSuperadmin();

        $request->validate([
            'table' => 'required|string',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);
        abort_if(! array_key_exists($request->table, tabelBisaArsip()), 404);

        $ids = collect($request->ids)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $restored = 0;

        foreach ($ids as $id) {
            $before = DB::table($request->table)->where('id', $id)->whereNotNull('deleted_at')->first();
            if (! $before) {
                continue;
            }

            $payload = ['deleted_at' => null];
            if (Schema::hasColumn($request->table, 'updated_at')) {
                $payload['updated_at'] = now();
            }

            try {
                DB::table($request->table)->where('id', $id)->update($payload);
                AuditLogger::record('restore', $request->table, (int) $id, 'Data dipulihkan massal dari arsip', $before, DB::table($request->table)->where('id', $id)->first(), $request);
                $restored++;
            } catch (QueryException $exception) {
                report($exception);
            }
        }

        return back()->with($restored ? 'success' : 'error', $restored ? $restored.' data berhasil dipulihkan dari arsip.' : 'Tidak ada data yang dipulihkan.');
    }

    public function forceDelete(Request $request)
    {
        wajibSuperadmin();

        $request->validate([
            'table' => 'required|string',
            'id' => 'required|integer',
        ]);
        abort_if(! array_key_exists($request->table, tabelBisaArsip()), 404);

        $before = DB::table($request->table)->where('id', $request->id)->whereNotNull('deleted_at')->first();
        abort_if(! $before, 404);

        try {
            DB::table($request->table)->where('id', $request->id)->whereNotNull('deleted_at')->delete();
            AuditLogger::record('force_delete', $request->table, (int) $request->id, 'Data arsip dihapus permanen', $before, null, $request);
        } catch (QueryException $exception) {
            report($exception);

            return back()->with('error', 'Data arsip gagal dihapus permanen karena masih terhubung dengan data lain.');
        }

        return back()->with('success', 'Data arsip berhasil dihapus permanen.');
    }

    public function bulkForceDelete(Request $request)
    {
        wajibSuperadmin();

        $request->validate([
            'table' => 'required|string',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);
        abort_if(! array_key_exists($request->table, tabelBisaArsip()), 404);

        $ids = collect($request->ids)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $deleted = 0;

        foreach ($ids as $id) {
            $before = DB::table($request->table)->where('id', $id)->whereNotNull('deleted_at')->first();
            if (! $before) {
                continue;
            }

            try {
                DB::table($request->table)->where('id', $id)->whereNotNull('deleted_at')->delete();
                AuditLogger::record('force_delete', $request->table, (int) $id, 'Data arsip dihapus permanen secara massal', $before, null, $request);
                $deleted++;
            } catch (QueryException $exception) {
                report($exception);
            }
        }

        return back()->with($deleted ? 'success' : 'error', $deleted ? $deleted.' data arsip berhasil dihapus permanen.' : 'Tidak ada data arsip yang dihapus.');
    }
}

