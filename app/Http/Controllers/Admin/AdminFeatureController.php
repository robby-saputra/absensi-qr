<?php

namespace App\Http\Controllers\Admin;

use App\Exports\KalenderExport;
use App\Exports\KalenderTemplateExport;
use App\Exports\RekapAbsensiExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AttendanceSettingService;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use ZipArchive;

class AdminFeatureController extends Controller
{
    public function editGuru($id)
    {
        $user = session('user');
        $guru = User::where('role', 'guru')->findOrFail($id);
        $before = $guru->only(['nama', 'nuptk', 'username', 'aktif']);

        return view('dashboard.guru.edit', compact('user', 'guru'));
    }

    public function updateGuru(Request $request, $id)
    {
        $guru = User::where('role', 'guru')->findOrFail($id);
        $before = $guru->only(['nama', 'nuptk', 'username', 'aktif']);

        $request->validate([
            'nama' => 'required',
            'nuptk' => 'nullable',
            'username' => ['required', Rule::unique('users', 'username')->ignore($guru->id)],
            'password' => 'nullable|min:4',
        ]);

        $data = [
            'nama' => $request->nama,
            'nuptk' => $request->nuptk,
            'username' => $request->username,
            'updated_at' => now(),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $guru->update($data);
        AuditLogger::record('update', 'users', $guru->id, 'Data guru diupdate', $before, $guru->fresh()->only(['nama', 'nuptk', 'username', 'aktif']), $request);

        return redirect('/dashboard/admin/guru')->with('success', 'Data guru berhasil diupdate');
    }

    public function editKelas($id)
    {
        $user = session('user');
        $kelas = DB::table('kelas')->where('id', $id)->first();

        abort_if(! $kelas, 404);

        $guru = User::where('role', 'guru')->where('aktif', 1)->whereNull('deleted_at')->orderBy('nama')->get();
        $jurusan = DB::table('jurusan')->orderBy('kode_jurusan')->get();

        return view('dashboard.kelas.edit', compact('user', 'kelas', 'guru', 'jurusan'));
    }

    public function updateKelas(Request $request, $id)
    {
        $kelas = DB::table('kelas')->where('id', $id)->first();
        abort_if(! $kelas, 404);

        $request->validate([
            'nama_kelas' => ['required', Rule::unique('kelas', 'nama_kelas')->ignore($id)],
            'jurusan_id' => 'required',
            'wali_kelas_id' => 'nullable',
        ]);

        if ($request->filled('wali_kelas_id')) {
            $dipakai = DB::table('kelas')
                ->where('wali_kelas_id', $request->wali_kelas_id)
                ->where('id', '!=', $id)
                ->exists();

            if ($dipakai) {
                return back()->with('error', 'Guru sudah menjadi wali kelas lain')->withInput();
            }

            if ($pesanGuruNonaktif = validasiGuruAktifIds([$request->wali_kelas_id])) {
                return back()->with('error', $pesanGuruNonaktif)->withInput();
            }
        }

        DB::table('kelas')
            ->where('id', $id)
            ->update([
                'nama_kelas' => $request->nama_kelas,
                'jurusan_id' => $request->jurusan_id,
                'wali_kelas_id' => $request->wali_kelas_id,
                'updated_at' => now(),
            ]);
        AuditLogger::record('update', 'kelas', (int) $id, 'Data kelas diupdate', $kelas, DB::table('kelas')->where('id', $id)->first(), $request);

        return redirect('/dashboard/admin/kelas')->with('success', 'Kelas berhasil diupdate');
    }

    public function editJurusan($id)
    {
        $user = session('user');
        $jurusan = DB::table('jurusan')->where('id', $id)->first();

        abort_if(! $jurusan, 404);

        return view('dashboard.jurusan.edit', compact('user', 'jurusan'));
    }

    public function updateJurusan(Request $request, $id)
    {
        $jurusan = DB::table('jurusan')->where('id', $id)->first();
        abort_if(! $jurusan, 404);

        $request->validate([
            'nama_jurusan' => 'required',
            'kode_jurusan' => 'required',
        ]);

        DB::table('jurusan')
            ->where('id', $id)
            ->update([
                'nama_jurusan' => $request->nama_jurusan,
                'kode_jurusan' => strtoupper($request->kode_jurusan),
                'updated_at' => now(),
            ]);
        AuditLogger::record('update', 'jurusan', (int) $id, 'Data jurusan diupdate', $jurusan, DB::table('jurusan')->where('id', $id)->first(), $request);

        return redirect('/dashboard/admin/jurusan')->with('success', 'Jurusan berhasil diupdate');
    }

    public function resetPasswordForm($id)
    {
        $user = session('user');
        $target = User::findOrFail($id);

        return view('dashboard.reset_password', compact('user', 'target'));
    }

    public function resetPassword(Request $request, $id)
    {
        $target = User::findOrFail($id);

        $request->validate([
            'password' => 'required|min:4|confirmed',
        ]);

        $target->update([
            'password' => Hash::make($request->password),
            'updated_at' => now(),
        ]);
        AuditLogger::record('reset_password', 'users', $target->id, 'Password user direset', ['username' => $target->username], ['password' => 'direset'], $request);

        return $this->backToUserList($target)->with('success', 'Password berhasil direset');
    }

    public function toggleActive($id)
    {
        $target = User::findOrFail($id);
        $current = session('user');

        if ($current && $current->id == $target->id) {
            return back()->with('error', 'Akun yang sedang login tidak bisa dinonaktifkan');
        }

        $target->update([
            'aktif' => ! (bool) ($target->aktif ?? true),
            'updated_at' => now(),
        ]);
        AuditLogger::record('toggle_active', 'users', $target->id, 'Status akun diubah', ['aktif' => ! (bool) $target->aktif], ['aktif' => (bool) $target->aktif], request());

        return back()->with('success', 'Status akun berhasil diubah');
    }

    public function importSiswaForm()
    {
        $user = session('user');

        return view('dashboard.siswa.import', compact('user'));
    }

    public function importJadwalForm()
    {
        $user = session('user');

        return view('dashboard.jadwal.import', compact('user'));
    }

    public function importJadwal(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt',
        ]);

        $path = $request->file('file')->getRealPath();
        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        $rows = $ext === 'xlsx' ? $this->readXlsx($path) : $this->readCsv($path);
        $result = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $tahunNama = trim($row['tahun_ajaran'] ?? '');
            $semester = strtolower(trim($row['semester'] ?? ''));
            $kelasNama = trim($row['kelas'] ?? '');
            $hari = ucfirst(strtolower(trim($row['hari'] ?? '')));
            $jamMulai = $this->normalizeTime($row['jam_mulai'] ?? '');
            $jamSelesai = $this->normalizeTime($row['jam_selesai'] ?? '');
            $mapelNama = trim($row['mapel'] ?? $row['mata_pelajaran'] ?? '');
            $guruNama = trim($row['guru'] ?? $row['guru_utama'] ?? '');

            if (! $kelasNama || ! $hari || ! $jamMulai || ! $jamSelesai || ! $mapelNama || ! $guruNama) {
                $this->addImportError($result, $line, 'Kelas, hari, jam, mapel, dan guru wajib diisi');

                continue;
            }

            $tahun = $this->findTahunAjaran($tahunNama, $semester);
            $kelas = DB::table('kelas')->where('nama_kelas', $kelasNama)->first();
            $mapel = DB::table('mapels')->where('nama_mapel', $mapelNama)->first();
            $guru = User::where('role', 'guru')->where('aktif', 1)->whereNull('deleted_at')->where(function ($query) use ($guruNama) {
                $query->where('nama', $guruNama)->orWhere('username', $guruNama);
            })->first();
            if (! $tahun || ! $kelas || ! $mapel || ! $guru) {
                $this->addImportError($result, $line, 'Tahun ajaran/kelas/mapel/guru tidak ditemukan');

                continue;
            }

            if ($jamMulai >= $jamSelesai) {
                $this->addImportError($result, $line, 'Jam selesai harus lebih besar dari jam mulai');

                continue;
            }

            $liburBerulang = DB::table('kalender_sekolahs')
                ->where('jenis', 'libur')
                ->where('berulang', 1)
                ->where('hari_berulang', strtolower($hari))
                ->where(function ($query) use ($tahun) {
                    $query->where('tahun_ajaran_id', $tahun->id)->orWhereNull('tahun_ajaran_id');
                })
                ->first();

            if ($liburBerulang) {
                $this->addImportError($result, $line, 'Hari '.$hari.' libur: '.$liburBerulang->judul);

                continue;
            }

            $conflict = $this->jadwalBentrok($tahun->id, $kelas->id, $hari, $jamMulai, $jamSelesai, $guru->id);
            if ($conflict) {
                $this->addImportError($result, $line, $conflict);

                continue;
            }

            $newId = DB::table('jadwal_pelajarans')->insertGetId([
                'tahun_ajaran_id' => $tahun->id,
                'kelas_id' => $kelas->id,
                'hari' => $hari,
                'jam_mulai' => $jamMulai,
                'jam_selesai' => $jamSelesai,
                'mapel_id' => $mapel->id,
                'guru_id' => $guru->id,
                'guru_pengganti_id' => null,
                'status_guru' => 'normal',
                'alasan_tidak_hadir' => null,
                'keterangan' => $row['keterangan'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            AuditLogger::record('create', 'jadwal_pelajarans', (int) $newId, 'Jadwal pelajaran diimport', null, DB::table('jadwal_pelajarans')->where('id', $newId)->first(), $request);
            $result['success']++;
        }

        return back()->with('import_result', $result);
    }

    public function importKalender(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt',
        ]);

        $path = $request->file('file')->getRealPath();
        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        $rows = $ext === 'xlsx' ? $this->readXlsx($path) : $this->readCsv($path);
        $result = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $tahun = $this->findTahunAjaran(trim($row['tahun_ajaran'] ?? ''), strtolower(trim($row['semester'] ?? '')));
            $tanggalMulai = $this->normalizeDate($row['tanggal_mulai'] ?? '');
            $tanggalSelesai = $this->normalizeDate($row['tanggal_selesai'] ?? $row['tanggal_mulai'] ?? '');
            $judul = trim($row['judul'] ?? '');
            $jenis = strtolower(trim($row['jenis'] ?? 'libur'));

            if (! $tanggalMulai || ! $tanggalSelesai || ! $judul || ! in_array($jenis, ['libur', 'kegiatan', 'ujian'])) {
                $this->addImportError($result, $line, 'Tanggal, judul, dan jenis wajib valid');

                continue;
            }

            if ($tanggalMulai > $tanggalSelesai) {
                $this->addImportError($result, $line, 'Tanggal selesai tidak boleh sebelum tanggal mulai');

                continue;
            }

            $exists = DB::table('kalender_sekolahs')
                ->whereDate('tanggal_mulai', $tanggalMulai)
                ->whereDate('tanggal_selesai', $tanggalSelesai)
                ->where('judul', $judul)
                ->exists();

            if ($exists) {
                $this->addImportError($result, $line, 'Data kalender sudah ada');

                continue;
            }

            $newId = DB::table('kalender_sekolahs')->insertGetId([
                'tahun_ajaran_id' => $tahun->id ?? null,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'judul' => $judul,
                'jenis' => $jenis,
                'provinsi' => trim($row['provinsi'] ?? '') ?: null,
                'sumber' => 'import',
                'keterangan' => $row['keterangan'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            AuditLogger::record('create', 'kalender_sekolahs', (int) $newId, 'Kalender sekolah diimport', null, DB::table('kalender_sekolahs')->where('id', $newId)->first(), $request);
            $result['success']++;
        }

        return back()->with('import_result', $result);
    }

    public function exportKalender(Request $request)
    {
        $rows = DB::table('kalender_sekolahs')
            ->when($request->get('tahun_ajaran_id'), fn ($query, $id) => $query->where('tahun_ajaran_id', $id))
            ->when($request->get('provinsi'), fn ($query, $provinsi) => $query->where(function ($where) use ($provinsi) {
                $where->where('provinsi', $provinsi)->orWhere('provinsi', 'Nasional')->orWhereNull('provinsi');
            }))
            ->orderBy('tanggal_mulai')
            ->get();

        return Excel::download(new KalenderExport($rows), 'kalender_sekolah_'.now()->format('Ymd_His').'.xlsx');
    }

    public function importSiswa(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt',
        ]);

        $path = $request->file('file')->getRealPath();

        $ext =
            strtolower(
                $request->file('file')
                    ->getClientOriginalExtension()
            );

        $rows =
            $ext === 'xlsx'
            ?
            $this->readXlsx($path)
            :
            $this->readCsv($path);

        $result = [

            'success' => 0,

            'failed' => 0,

            'errors' => [],

        ];

        foreach ($rows as $index => $row) {

            $line = $index + 2;

            $nama =
            trim(
                $row['nama']
                ??
                $row['nama_siswa']
                ??
                ''
            );

            $nis =
            trim(
                $row['nis']
                ??
                ''
            );

            $username =
            trim(
                $row['username']
                ??
                ''
            );

            $password =
            trim(
                $row['password']
                ??
                ''
            );

            $noOrtu =
            trim(

                $row['no_ortu']

                ??

                ''

            );

            $namaOrtu = trim(
                $row['nama_ortu']
                ?? $row['nama_orang_tua']
                ?? ''
            );

            $status =
            strtolower(

                trim(

                    $row['status']

                    ??

                    'aktif'

                )

            );

            $kelasInput =
            trim(

                $row['kelas']

                ??

                $row['kelas_id']

                ??

                ''

            );

            if (

                $nama == ''

                ||

                $username == ''

                ||

                $kelasInput == ''

            ) {

                $this->addImportError(

                    $result,

                    $line,

                    'Nama, username dan kelas wajib'

                );

                continue;

            }

            if (

                User::where(

                    'username',

                    $username

                )->exists()

            ) {

                $this->addImportError(

                    $result,

                    $line,

                    "Username {$username} sudah dipakai"

                );

                continue;

            }

            $kelas =

            DB::table('kelas')
                ->where(

                    'nama_kelas',

                    $kelasInput

                )
                ->first();

            if (! $kelas) {

                $this->addImportError(

                    $result,

                    $line,

                    "Kelas {$kelasInput} tidak ditemukan"

                );

                continue;

            }

            User::create([

                'nama' => $nama,

                'nis' => $nis ?: null,

                'username' => $username,

                'password' => Hash::make(

                    $password

                    ?:

                    '123456'

                ),

                'role' => 'siswa',

                'kelas_id' => $kelas->id,

                'no_ortu' => $noOrtu,
                'nama_ortu' => $namaOrtu ?: null,

                'aktif' => in_array(

                    $status,

                    [

                        'aktif',

                        '1',

                        'true',

                    ]

                ),

            ]);

            $result['success']++;

        }

        return back()
            ->with(

                'import_result',

                $result

            );

    }

    public function rekapAbsensi(Request $request)
    {
        $user = session('user');
        $filters = $this->absensiFilters($request);
        $absensi = $this->absensiQuery($filters)->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $libur = null;

        if ($filters['mode'] === 'tanggal') {
            $libur = DB::table('kalender_sekolahs')
                ->where('jenis', 'libur')
                ->whereDate('tanggal_mulai', '<=', $filters['tanggal'])
                ->whereDate('tanggal_selesai', '>=', $filters['tanggal'])
                ->first();
        }

        return view('dashboard.absensi_rekap', compact('user', 'absensi', 'filters', 'tahunAjaran', 'libur'));
    }

    public function exportAbsensi(Request $request)
    {
        $filters = $this->absensiFilters($request);

        return Excel::download(

            new RekapAbsensiExport(

                $filters

            ),

            'rekap_absensi_'.

            now()->format(

                'Ymd_His'

            )

            .

            '.xlsx'

        );
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $headers = null;
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = $this->normalizeHeaders($data);

                continue;
            }

            if (count(array_filter($data, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $rows[] = array_combine($headers, array_pad($data, count($headers), null));
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = $this->readSharedStrings($zip);
        $sheetContent = $zip->getFromName('xl/worksheets/sheet1.xml');

        if (! $sheetContent) {
            $zip->close();

            return [];
        }

        $sheetXml = simplexml_load_string($sheetContent);
        $rawRows = [];

        foreach ($sheetXml->sheetData->row as $row) {
            $cells = [];

            foreach ($row->c as $cell) {
                $cellRef = (string) $cell['r'];
                $column = $this->columnIndex($cellRef);
                $type = (string) $cell['t'];
                $value = '';

                if ($type === 's') {
                    $value = $sharedStrings[(int) $cell->v] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) $cell->is->t;
                } else {
                    $value = (string) $cell->v;
                }

                $cells[$column] = $value;
            }

            if ($cells) {
                ksort($cells);
                $rawRows[] = $cells;
            }
        }

        $zip->close();

        if (! $rawRows) {
            return [];
        }

        $headers = $this->normalizeHeaders(array_values($rawRows[0]));
        $rows = [];

        foreach (array_slice($rawRows, 1) as $rawRow) {
            $values = [];

            for ($i = 0; $i < count($headers); $i++) {
                $values[] = $rawRow[$i] ?? null;
            }

            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $rows[] = array_combine($headers, $values);
        }

        return $rows;
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if (! $xml) {
            return [];
        }

        $strings = [];
        $shared = simplexml_load_string($xml);

        foreach ($shared->si as $item) {
            if (isset($item->t)) {
                $strings[] = (string) $item->t;

                continue;
            }

            $text = '';
            foreach ($item->r as $run) {
                $text .= (string) $run->t;
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            return str_replace([' ', '-'], '_', strtolower(trim((string) $header)));
        }, $headers);
    }

    private function columnIndex(string $cellRef): int
    {
        preg_match('/^[A-Z]+/', $cellRef, $matches);
        $letters = $matches[0] ?? 'A';
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function addImportError(array &$result, int $line, string $message): void
    {
        $result['failed']++;
        $result['errors'][] = "Baris {$line}: {$message}";
    }

    private function absensiFilters(Request $request): array
    {
        $mode = $request->get('mode', 'tanggal');

        return [
            'mode' => $mode,
            'tanggal' => $request->get('tanggal', now()->toDateString()),
            'bulan' => $request->get('bulan', now()->format('Y-m')),
            'tahun_ajaran_id' => $request->get('tahun_ajaran_id') ?: DB::table('tahun_ajarans')->where('aktif', true)->value('id'),
            'status_default_alfa' => AttendanceSettingService::statusDefaultAlfa(),
        ];
    }

    private function absensiQuery(array $filters)
    {
        $query = DB::table('absensis as a')
            ->join('users as s', 's.id', '=', 'a.id_siswa')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->select(
                'a.*',
                's.nama',
                's.nis',
                'k.nama_kelas'
            )
            ->orderByDesc('a.tanggal')
            ->orderBy('s.nama');

        if (Schema::hasColumn('absensis', 'deleted_at')) {
            $query->whereNull('a.deleted_at');
        }

        if (! empty($filters['tahun_ajaran_id'])) {
            $tahunAjaran = DB::table('tahun_ajarans')->where('id', $filters['tahun_ajaran_id'])->first();

            $query->where(function ($tahun) use ($filters, $tahunAjaran) {
                $tahun->where('a.tahun_ajaran_id', $filters['tahun_ajaran_id']);

                if ($tahunAjaran) {
                    $tahun->orWhere(function ($legacy) use ($tahunAjaran) {
                        $legacy->whereNull('a.tahun_ajaran_id')
                            ->whereDate('a.tanggal', '>=', $tahunAjaran->tanggal_mulai)
                            ->whereDate('a.tanggal', '<=', $tahunAjaran->tanggal_selesai);
                    });
                }
            });
        }

        if ($filters['mode'] === 'bulan') {
            return $query->whereYear('a.tanggal', substr($filters['bulan'], 0, 4))
                ->whereMonth('a.tanggal', substr($filters['bulan'], 5, 2));
        }

        return $query->whereDate('a.tanggal', $filters['tanggal']);
    }

    public function downloadTemplateSiswa()
    {

        $data = [

            [

                'nis' => '9939393',

                'nama' => 'panjul',

                'username' => 'panjul',

                'password' => '123456',

                'kelas' => 'X AK 1',

                'jurusan' => 'AK',

                'wali_kelas' => 'Hendra Saputra',

                'nama_ortu' => 'Orang Tua Panjul',

                'no_ortu' => '085656565',

                'status' => 'aktif',

            ],

        ];

        return Excel::download(

            new class($data) implements FromArray, ShouldAutoSize, WithHeadings
            {
                protected $data;

                public function __construct($data)
                {

                    $this->data = $data;

                }

                public function headings(): array
                {

                    return [

                        'nis',

                        'nama',

                        'username',

                        'password',

                        'kelas',

                        'jurusan',

                        'wali_kelas',

                        'nama_ortu',

                        'no_ortu',

                        'status',

                    ];

                }

                public function array(): array
                {

                    return $this->data;

                }
            },

            'template_import_siswa.xlsx'

        );

    }

    public function downloadTemplateJadwal()
    {
        $tahun = DB::table('tahun_ajarans')->where('aktif', true)->first();
        $kelas = DB::table('kelas')->orderBy('nama_kelas')->value('nama_kelas') ?? 'X AK 1';
        $mapel = DB::table('mapels')->orderBy('nama_mapel')->value('nama_mapel') ?? 'Matematika';
        $guru = User::where('role', 'guru')->orderBy('nama')->value('nama') ?? 'Nama Guru';

        $data = [[
            'tahun_ajaran' => $tahun->nama ?? '2026/2027',
            'semester' => $tahun->semester ?? 'ganjil',
            'kelas' => $kelas,
            'hari' => 'Senin',
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:30',
            'mapel' => $mapel,
            'guru' => $guru,
            'keterangan' => '',
        ]];

        return Excel::download(
            new class($data) implements FromArray, ShouldAutoSize, WithHeadings
            {
                public function __construct(private array $data) {}

                public function headings(): array
                {
                    return ['tahun_ajaran', 'semester', 'kelas', 'hari', 'jam_mulai', 'jam_selesai', 'mapel', 'guru', 'keterangan'];
                }

                public function array(): array
                {
                    return $this->data;
                }
            },
            'template_import_jadwal.xlsx'
        );
    }

    public function downloadTemplateKalender()
    {
        $tahun = DB::table('tahun_ajarans')->where('aktif', true)->first();
        $tahunAjaran = DB::table('tahun_ajarans')
            ->orderByDesc('tanggal_mulai')
            ->get(['nama', 'semester'])
            ->map(fn ($item) => ['nama' => $item->nama, 'semester' => $item->semester])
            ->values()
            ->all();
        $provinsi = [
            'Nasional', 'Aceh', 'Sumatera Utara', 'Sumatera Barat', 'Riau', 'Kepulauan Riau',
            'Jambi', 'Bengkulu', 'Sumatera Selatan', 'Bangka Belitung', 'Lampung', 'Banten',
            'DKI Jakarta', 'Jawa Barat', 'Jawa Tengah', 'DI Yogyakarta', 'Jawa Timur', 'Bali',
            'Nusa Tenggara Barat', 'Nusa Tenggara Timur', 'Kalimantan Barat', 'Kalimantan Tengah',
            'Kalimantan Selatan', 'Kalimantan Timur', 'Kalimantan Utara', 'Sulawesi Utara',
            'Gorontalo', 'Sulawesi Tengah', 'Sulawesi Barat', 'Sulawesi Selatan',
            'Sulawesi Tenggara', 'Maluku', 'Maluku Utara', 'Papua', 'Papua Barat',
        ];
        $data = [[
            'tahun_ajaran' => $tahun->nama ?? '2026/2027',
            'semester' => $tahun->semester ?? 'ganjil',
            'tanggal_mulai' => now()->format('Y-m-d'),
            'tanggal_selesai' => now()->format('Y-m-d'),
            'judul' => 'Libur Sekolah',
            'jenis' => 'libur',
            'provinsi' => 'Nasional',
            'keterangan' => 'Contoh data kalender pendidikan',
        ], [
            'tahun_ajaran' => $tahun->nama ?? '2026/2027',
            'semester' => $tahun->semester ?? 'ganjil',
            'tanggal_mulai' => now()->addWeek()->format('Y-m-d'),
            'tanggal_selesai' => now()->addWeek()->format('Y-m-d'),
            'judul' => 'Kegiatan Sekolah',
            'jenis' => 'kegiatan',
            'provinsi' => '',
            'keterangan' => 'Contoh kegiatan, tidak otomatis libur',
        ]];

        return Excel::download(
            new KalenderTemplateExport($data, $tahunAjaran, $provinsi),
            'template_kalender_pendidikan.xlsx'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REDIRECT LIST USER
    |--------------------------------------------------------------------------
    */

    private function backToUserList(User $target)
    {

        if ($target->role === 'guru') {

            return redirect('/dashboard/admin/guru');

        }

        if ($target->role === 'siswa') {

            return redirect('/dashboard/admin/siswa');

        }

        return redirect('/dashboard/admin');

    }

    private function findTahunAjaran(string $nama, string $semester)
    {
        $query = DB::table('tahun_ajarans');

        if ($nama) {
            $query->where('nama', $nama);
        }

        if ($semester) {
            $query->where('semester', $semester);
        }

        return ($nama || $semester) ? $query->first() : DB::table('tahun_ajarans')->where('aktif', true)->first();
    }

    private function normalizeTime($value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $seconds = (int) round(((float) $value) * 86400);

            return gmdate('H:i:s', $seconds);
        }

        if (preg_match('/^\d{1,2}:\d{2}$/', $value)) {
            return $value.':00';
        }

        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $value)) {
            return $value;
        }

        return null;
    }

    private function normalizeDate($value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::create(1899, 12, 30)->addDays((int) $value)->toDateString();
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function jadwalBentrok(int $tahunAjaranId, int $kelasId, string $hari, string $jamMulai, string $jamSelesai, int $guruId): ?string
    {
        $kelasBentrok = DB::table('jadwal_pelajarans')
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->whereRaw('LOWER(hari) = ?', [strtolower($hari)])
            ->where('kelas_id', $kelasId)
            ->where('jam_mulai', '<', $jamSelesai)
            ->where('jam_selesai', '>', $jamMulai)
            ->exists();

        if ($kelasBentrok) {
            return 'Kelas bentrok pada hari dan jam yang sama';
        }

        $guruIds = array_values(array_filter([$guruId]));

        $guruBentrok = DB::table('jadwal_pelajarans')
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->whereRaw('LOWER(hari) = ?', [strtolower($hari)])
            ->whereIn('guru_id', $guruIds)
            ->where('jam_mulai', '<', $jamSelesai)
            ->where('jam_selesai', '>', $jamMulai)
            ->exists();

        if ($guruBentrok) {
            return 'Guru bentrok pada hari dan jam yang sama';
        }

        $piketBentrok = DB::table('guru_pikets')
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->whereRaw('LOWER(hari) = ?', [strtolower($hari)])
            ->where('aktif', 1)
            ->whereIn('guru_id', $guruIds)
            ->where('jam_mulai', '<', $jamSelesai)
            ->where('jam_selesai', '>', $jamMulai)
            ->exists();

        return $piketBentrok ? 'Guru sedang piket pada hari dan jam yang sama' : null;
    }
}
