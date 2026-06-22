<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JurusanController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user');
        $filters = $request->only(['search', 'kelas_status', 'sort']);

        $query = tanpaArsip(DB::table('jurusan as j'), 'jurusan', 'j')
            ->select('j.*')
            ->selectSub(function ($subquery) {
                $subquery->from('kelas as k')->selectRaw('COUNT(*)')->whereColumn('k.jurusan_id', 'j.id')->whereNull('k.deleted_at');
            }, 'jumlah_kelas')
            ->selectSub(function ($subquery) {
                $subquery->from('users as s')->join('kelas as k', 'k.id', '=', 's.kelas_id')->selectRaw('COUNT(*)')->whereColumn('k.jurusan_id', 'j.id')->where('s.role', 'siswa')->where('s.aktif', 1)->whereNull('s.deleted_at')->whereNull('k.deleted_at');
            }, 'jumlah_siswa')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($where) use ($search) {
                    $where->where('j.nama_jurusan', 'like', '%'.$search.'%')->orWhere('j.kode_jurusan', 'like', '%'.$search.'%');
                });
            })
            ->when(($filters['kelas_status'] ?? null) === 'terpakai', fn ($query) => $query->whereExists(function ($subquery) {
                $subquery->selectRaw('1')->from('kelas as k')->whereColumn('k.jurusan_id', 'j.id')->whereNull('k.deleted_at');
            }))
            ->when(($filters['kelas_status'] ?? null) === 'kosong', fn ($query) => $query->whereNotExists(function ($subquery) {
                $subquery->selectRaw('1')->from('kelas as k')->whereColumn('k.jurusan_id', 'j.id')->whereNull('k.deleted_at');
            }));

        $jurusan = (match ($filters['sort'] ?? 'nama') {
            'kode' => $query->orderBy('j.kode_jurusan'),
            'terbaru' => $query->orderByDesc('j.id'),
            default => $query->orderBy('j.nama_jurusan'),
        })->get();

        return view('dashboard.jurusan.index', compact(
            'user',
            'jurusan',
            'filters'
        ));
    }

    public function create()
    {
        $user = session('user');

        return view('dashboard.jurusan.create', compact(
            'user'
        ));
    }

    public function detail($id)
    {
        $user = session('user');
        $jurusan = tanpaArsip(DB::table('jurusan as j'), 'jurusan', 'j')->where('j.id', $id)->first();
        abort_if(! $jurusan, 404);

        $kelas = tanpaArsip(DB::table('kelas as k'), 'kelas', 'k')
            ->leftJoin('users as wali', 'wali.id', '=', 'k.wali_kelas_id')
            ->where('k.jurusan_id', $id)
            ->select('k.*', 'wali.nama as nama_wali')
            ->selectSub(function ($subquery) {
                $subquery->from('users as siswa')->selectRaw('COUNT(*)')->whereColumn('siswa.kelas_id', 'k.id')->where('siswa.role', 'siswa')->where('siswa.aktif', 1)->whereNull('siswa.deleted_at');
            }, 'jumlah_siswa')
            ->orderBy('k.nama_kelas')->get();

        return view('dashboard.jurusan.detail', compact('user', 'jurusan', 'kelas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_jurusan' => 'required',
            'kode_jurusan' => 'required',
        ]);

        DB::table('jurusan')->insert([

            'nama_jurusan' => $request->nama_jurusan,
            'kode_jurusan' => $request->kode_jurusan,

            'created_at' => now(),
            'updated_at' => now(),

        ]);

        return redirect('/dashboard/admin/jurusan')
            ->with('success', 'Jurusan berhasil ditambahkan');
    }

    public function delete($id)
    {
        if (! hapusDataAdmin('jurusan', (int) $id, 'Jurusan', request())) {
            return redirect('/dashboard/admin/jurusan')
                ->with('error', 'Jurusan gagal dihapus atau data tidak ditemukan.');
        }

        return redirect('/dashboard/admin/jurusan')
            ->with('success', 'Jurusan berhasil dipindahkan ke arsip');
    }
}
