<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Controller ini mengelola data yang sudah diarsipkan atau soft delete.
class ArsipController extends Controller
{
    // Daftar resource menentukan tabel mana saja yang bisa dilihat dan dipulihkan dari arsip.
    private array $resources = [
        'guru-piket' => ['table' => 'guru_pikets', 'label' => 'Guru Piket', 'title' => 'hari'],
        'jadwal' => ['table' => 'jadwal_pelajarans', 'label' => 'Jadwal Pelajaran', 'title' => 'hari'],
        'siswa' => ['table' => 'users', 'label' => 'Siswa', 'title' => 'nama', 'where' => ['role' => 'siswa']],
        'guru' => ['table' => 'users', 'label' => 'Guru', 'title' => 'nama', 'where' => ['role' => 'guru']],
        'kelas' => ['table' => 'kelas', 'label' => 'Kelas', 'title' => 'nama_kelas'],
        'jurusan' => ['table' => 'jurusan', 'label' => 'Jurusan', 'title' => 'nama_jurusan'],
        'tahun-ajaran' => ['table' => 'tahun_ajarans', 'label' => 'Tahun Ajaran', 'title' => 'nama'],
        'kalender' => ['table' => 'kalender_sekolahs', 'label' => 'Kalender Sekolah', 'title' => 'judul'],
        'absensi' => ['table' => 'absensis', 'label' => 'Absensi Harian', 'title' => 'tanggal'],
        'absensi-mapel' => ['table' => 'absensi_mapels', 'label' => 'Absensi Mapel', 'title' => 'tanggal'],
        'pengajuan-izin' => ['table' => 'student_permit_requests', 'label' => 'Pengajuan Izin', 'title' => 'jenis'],
    ];

    // Menampilkan daftar data arsip berdasarkan resource yang dipilih admin.
    public function index(Request $request)
    {
        $resource = $request->get('resource', 'guru-piket');
        $config = $this->resources[$resource] ?? $this->resources['guru-piket'];
        $resource = array_key_exists($resource, $this->resources) ? $resource : 'guru-piket';

        $items = collect();
        if (Schema::hasTable($config['table']) && Schema::hasColumn($config['table'], 'deleted_at')) {
            $query = DB::table($config['table']);

            if ($resource === 'guru-piket') {
                $query
                    ->leftJoin('users as guru_utama', 'guru_utama.id', '=', 'guru_pikets.guru_id')
                    ->leftJoin('users as guru_pengganti', 'guru_pengganti.id', '=', 'guru_pikets.guru_pengganti_id')
                    ->select('guru_pikets.*', 'guru_utama.nama as nama_guru', 'guru_pengganti.nama as nama_pengganti')
                    ->whereNotNull('guru_pikets.deleted_at')
                    ->orderByDesc('guru_pikets.deleted_at');
            } else {
                $query
                    ->whereNotNull('deleted_at')
                    ->orderByDesc('deleted_at');
            }

            foreach ($config['where'] ?? [] as $column => $value) {
                $query->where($column, $value);
            }

            if ($request->filled('q')) {
                if ($resource === 'guru-piket') {
                    $query->where(function ($search) use ($request) {
                        $search->where('guru_utama.nama', 'like', '%'.$request->q.'%')
                            ->orWhere('guru_pengganti.nama', 'like', '%'.$request->q.'%')
                            ->orWhere('guru_pikets.hari', 'like', '%'.$request->q.'%')
                            ->orWhere('guru_pikets.status', 'like', '%'.$request->q.'%');
                    });
                } else {
                    $this->applySearch($query, $config['table'], $request->q);
                }
            }

            $items = $query->paginate(15)->withQueryString();
        }

        $stats = collect($this->resources)->mapWithKeys(function ($item, $key) {
            $count = 0;
            if (Schema::hasTable($item['table']) && Schema::hasColumn($item['table'], 'deleted_at')) {
                $query = DB::table($item['table'])->whereNotNull('deleted_at');
                foreach ($item['where'] ?? [] as $column => $value) {
                    $query->where($column, $value);
                }
                $count = $query->count();
            }

            return [$key => $count];
        });

        return view('dashboard.arsip.index', [
            'user' => session('user'),
            'items' => $items,
            'resources' => $this->resources,
            'resource' => $resource,
            'config' => $config,
            'stats' => $stats,
            'q' => $request->q,
        ]);
    }

    public function restore(Request $request, string $resource, int $id)
    {
        $config = $this->resourceOrFail($resource);

        $query = DB::table($config['table'])
            ->where('id', $id)
            ->whereNotNull('deleted_at');

        foreach ($config['where'] ?? [] as $column => $value) {
            $query->where($column, $value);
        }

        $before = (clone $query)->first();
        $conflict = $this->activeConflict($config['table'], $before);
        if ($conflict) {
            return redirect('/dashboard/admin/arsip?resource='.$resource)->with('error', 'Restore ditolak karena data aktif yang sama sudah tersedia.');
        }
        $restored = $query->update([
                'deleted_at' => null,
                'updated_at' => now(),
            ]);
        if ($restored) {
            app(\App\Services\AttendanceAuditService::class)->record('restore', $config['table'], $id, $before, (object) array_merge((array) $before, ['deleted_at' => null]), $request);
        }

        return redirect('/dashboard/admin/arsip?resource='.$resource)
            ->with($restored ? 'success' : 'error', $restored ? 'Data berhasil dipulihkan.' : 'Data arsip tidak ditemukan.');
    }

    public function destroy(Request $request, string $resource, int $id)
    {
        $config = $this->resourceOrFail($resource);

        $query = DB::table($config['table'])
            ->where('id', $id)
            ->whereNotNull('deleted_at');

        foreach ($config['where'] ?? [] as $column => $value) {
            $query->where($column, $value);
        }

        $before = (clone $query)->first();
        $deleted = $query->delete();
        if ($deleted) {
            app(\App\Services\AttendanceAuditService::class)->record('force_delete', $config['table'], $id, $before, null, $request, $request->input('alasan'));
        }

        return redirect('/dashboard/admin/arsip?resource='.$resource)
            ->with($deleted ? 'success' : 'error', $deleted ? 'Data arsip berhasil dihapus permanen.' : 'Data arsip tidak ditemukan.');
    }

    public function bulkDestroy(Request $request, string $resource)
    {
        $config = $this->resourceOrFail($resource);

        $ids = collect($request->input('ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return redirect('/dashboard/admin/arsip?resource='.$resource)
                ->with('error', 'Pilih minimal satu data arsip.');
        }

        $query = DB::table($config['table'])
            ->whereIn('id', $ids)
            ->whereNotNull('deleted_at');

        foreach ($config['where'] ?? [] as $column => $value) {
            $query->where($column, $value);
        }

        $deleted = $query->delete();

        return redirect('/dashboard/admin/arsip?resource='.$resource)
            ->with('success', $deleted.' data arsip berhasil dihapus permanen.');
    }

    public function empty(string $resource)
    {
        $config = $this->resourceOrFail($resource);

        $query = DB::table($config['table'])
            ->whereNotNull('deleted_at');

        foreach ($config['where'] ?? [] as $column => $value) {
            $query->where($column, $value);
        }

        $deleted = $query->delete();

        return redirect('/dashboard/admin/arsip?resource='.$resource)
            ->with('success', $deleted.' data arsip '.$config['label'].' berhasil dikosongkan.');
    }

    public function emptyAll()
    {
        $deleted = 0;

        foreach ($this->resources as $config) {
            if (! Schema::hasTable($config['table']) || ! Schema::hasColumn($config['table'], 'deleted_at')) {
                continue;
            }

            $query = DB::table($config['table'])->whereNotNull('deleted_at');

            foreach ($config['where'] ?? [] as $column => $value) {
                $query->where($column, $value);
            }

            $deleted += $query->delete();
        }

        return redirect('/dashboard/admin/arsip')
            ->with('success', $deleted.' data arsip berhasil dikosongkan dari semua kategori.');
    }

    private function resourceOrFail(string $resource): array
    {
        abort_if(! isset($this->resources[$resource]), 404);

        $config = $this->resources[$resource];
        abort_if(! Schema::hasTable($config['table']) || ! Schema::hasColumn($config['table'], 'deleted_at'), 404);

        return $config;
    }

    private function activeConflict(string $table, ?object $row): bool
    {
        if (! $row) return false;
        $keys = match ($table) {
            'absensis' => ['id_siswa', 'tanggal'],
            'absensi_mapels' => ['siswa_id', 'jadwal_id', 'tanggal'],
            'guru_piket_statuses' => ['guru_piket_id', 'guru_id', 'tanggal'],
            default => [],
        };
        if ($keys === []) return false;
        $query = DB::table($table)->whereNull('deleted_at')->where('id', '!=', $row->id);
        foreach ($keys as $key) $query->where($key, $row->{$key});
        return $query->exists();
    }

    private function applySearch($query, string $table, string $keyword): void
    {
        $columns = collect(['nama', 'username', 'nis', 'nama_kelas', 'nama_jurusan', 'judul', 'hari', 'tanggal', 'jenis', 'status'])
            ->filter(fn ($column) => Schema::hasColumn($table, $column))
            ->values();

        if ($columns->isEmpty()) {
            return;
        }

        $query->where(function ($search) use ($columns, $keyword) {
            foreach ($columns as $column) {
                $search->orWhere($column, 'like', '%'.$keyword.'%');
            }
        });
    }
}
