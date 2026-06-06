<?php

namespace App\Http\Controllers\Admin;

use App\Support\AuditLogger;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class KalenderSekolahController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user');
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?: tahunAjaranAktifId();
        $provinsi = $request->get('provinsi', 'Banten');
        $bulan = $request->get('bulan', now()->format('Y-m'));
        $monthStart = Carbon::parse($bulan.'-01')->startOfMonth();
        $monthEnd = (clone $monthStart)->endOfMonth();

        $kalender = DB::table('kalender_sekolahs')
            ->when($tahunAjaranId, fn ($query) => $query->where(function ($where) use ($tahunAjaranId) {
                $where->where('tahun_ajaran_id', $tahunAjaranId)->orWhereNull('tahun_ajaran_id');
            }))
            ->when($provinsi, fn ($query) => $query->where(function ($where) use ($provinsi) {
                $where->where('provinsi', $provinsi)->orWhere('provinsi', 'Nasional')->orWhereNull('provinsi');
            }))
            ->orderByDesc('tanggal_mulai')
            ->get();

        $kalenderBulan = $kalender
            ->filter(fn ($item) => $item->tanggal_mulai <= $monthEnd->toDateString() && $item->tanggal_selesai >= $monthStart->toDateString())
            ->values();
        $provinsiList = daftarProvinsiIndonesia();

        return view('dashboard.kalender_sekolah.index', compact('user', 'kalender', 'tahunAjaran', 'tahunAjaranId', 'provinsi', 'provinsiList', 'bulan', 'monthStart', 'kalenderBulan'));
    }

    public function create()
    {
        $user = session('user');
        $mode = 'create';
        $kalender = null;
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $provinsiList = daftarProvinsiIndonesia();
        $defaultProvinsi = 'Banten';

        return view('dashboard.kalender_sekolah.form', compact('user', 'mode', 'kalender', 'tahunAjaran', 'provinsiList', 'defaultProvinsi'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:150',
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
            'jenis' => 'required|in:libur,kegiatan,ujian',
            'provinsi' => 'nullable|string|max:80',
            'hari_berulang' => 'nullable|in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keterangan' => 'nullable|string',
        ]);

        $newId = DB::table('kalender_sekolahs')->insertGetId([
            'tahun_ajaran_id' => $request->tahun_ajaran_id ?: null,
            'judul' => $request->judul,
            'jenis' => $request->jenis,
            'provinsi' => $request->provinsi ?: null,
            'sumber' => 'manual',
            'berulang' => $request->has('berulang') ? 1 : 0,
            'hari_berulang' => $request->has('berulang') ? $request->hari_berulang : null,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'keterangan' => $request->keterangan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditLogger::record('create', 'kalender_sekolahs', (int) $newId, 'Kalender sekolah ditambahkan', null, DB::table('kalender_sekolahs')->where('id', $newId)->first(), $request);

        return redirect('/dashboard/admin/kalender-sekolah')->with('success', 'Kalender sekolah berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $user = session('user');
        $mode = 'edit';
        $kalender = DB::table('kalender_sekolahs')->where('id', $id)->first();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $provinsiList = daftarProvinsiIndonesia();
        $defaultProvinsi = 'Banten';

        abort_if(! $kalender, 404);

        return view('dashboard.kalender_sekolah.form', compact('user', 'mode', 'kalender', 'tahunAjaran', 'provinsiList', 'defaultProvinsi'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'judul' => 'required|string|max:150',
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
            'jenis' => 'required|in:libur,kegiatan,ujian',
            'provinsi' => 'nullable|string|max:80',
            'hari_berulang' => 'nullable|in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keterangan' => 'nullable|string',
        ]);

        $before = DB::table('kalender_sekolahs')->where('id', $id)->first();
        abort_if(! $before, 404);

        DB::table('kalender_sekolahs')->where('id', $id)->update([
            'tahun_ajaran_id' => $request->tahun_ajaran_id ?: null,
            'judul' => $request->judul,
            'jenis' => $request->jenis,
            'provinsi' => $request->provinsi ?: null,
            'berulang' => $request->has('berulang') ? 1 : 0,
            'hari_berulang' => $request->has('berulang') ? $request->hari_berulang : null,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'keterangan' => $request->keterangan,
            'updated_at' => now(),
        ]);

        AuditLogger::record('update', 'kalender_sekolahs', (int) $id, 'Kalender sekolah diupdate', $before, DB::table('kalender_sekolahs')->where('id', $id)->first(), $request);

        return redirect('/dashboard/admin/kalender-sekolah')->with('success', 'Kalender sekolah berhasil diupdate.');
    }

    public function delete($id)
    {
        $before = DB::table('kalender_sekolahs')->where('id', $id)->first();
        abort_if(! $before, 404);

        if (! hapusDataAdmin('kalender_sekolahs', (int) $id, 'Kalender sekolah', request())) {
            return back()->with('error', 'Kalender sekolah gagal dihapus atau data tidak ditemukan.');
        }

        return back()->with('success', 'Kalender sekolah berhasil dihapus.');
    }

    public function autoNasional(Request $request)
    {
        $request->validate([
            'tahun' => 'required|integer|min:2020|max:2100',
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
            'provinsi' => 'nullable|string|max:80',
        ]);

        $response = Http::timeout(12)->get('https://date.nager.at/api/v3/PublicHolidays/'.$request->tahun.'/ID');

        if (! $response->successful()) {
            return back()->with('error', 'Gagal mengambil tanggal merah nasional. Coba lagi nanti atau import Excel.');
        }

        $created = 0;

        foreach ($response->json() as $holiday) {
            $tanggal = $holiday['date'] ?? null;
            $judul = $holiday['localName'] ?? $holiday['name'] ?? 'Tanggal Merah Nasional';

            if (! $tanggal) {
                continue;
            }

            $exists = DB::table('kalender_sekolahs')
                ->whereDate('tanggal_mulai', $tanggal)
                ->whereDate('tanggal_selesai', $tanggal)
                ->where('judul', $judul)
                ->where('sumber', 'nasional')
                ->exists();

            if ($exists) {
                continue;
            }

            $newId = DB::table('kalender_sekolahs')->insertGetId([
                'tahun_ajaran_id' => $request->tahun_ajaran_id ?: null,
                'tanggal_mulai' => $tanggal,
                'tanggal_selesai' => $tanggal,
                'judul' => $judul,
                'jenis' => 'libur',
                'provinsi' => $request->provinsi ?: 'Nasional',
                'sumber' => 'nasional',
                'keterangan' => 'Tanggal merah nasional otomatis',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            AuditLogger::record('create', 'kalender_sekolahs', (int) $newId, 'Tanggal merah nasional otomatis', null, DB::table('kalender_sekolahs')->where('id', $newId)->first(), $request);
            $created++;
        }

        return back()->with('success', 'Tanggal merah nasional berhasil diisi: '.$created.' data baru.');
    }
}

