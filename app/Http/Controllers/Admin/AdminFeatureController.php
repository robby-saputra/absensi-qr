<?php

namespace App\Http\Controllers\Admin;

use App\Exports\RekapAbsensiExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

        return view('dashboard.guru.edit', compact('user', 'guru'));
    }

    public function updateGuru(Request $request, $id)
    {
        $guru = User::where('role', 'guru')->findOrFail($id);

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

        return redirect('/dashboard/admin/guru')->with('success', 'Data guru berhasil diupdate');
    }

    public function editKelas($id)
    {
        $user = session('user');
        $kelas = DB::table('kelas')->where('id', $id)->first();

        abort_if(! $kelas, 404);

        $guru = User::where('role', 'guru')->orderBy('nama')->get();
        $jurusan = DB::table('jurusan')->orderBy('kode_jurusan')->get();

        return view('dashboard.kelas.edit', compact('user', 'kelas', 'guru', 'jurusan'));
    }

    public function updateKelas(Request $request, $id)
    {
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
        }

        DB::table('kelas')
            ->where('id', $id)
            ->update([
                'nama_kelas' => $request->nama_kelas,
                'jurusan_id' => $request->jurusan_id,
                'wali_kelas_id' => $request->wali_kelas_id,
                'updated_at' => now(),
            ]);

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

        return back()->with('success', 'Status akun berhasil diubah');
    }

    public function importSiswaForm()
    {
        $user = session('user');

        return view('dashboard.siswa.import', compact('user'));
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

        return view('dashboard.absensi_rekap', compact('user', 'absensi', 'filters'));
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
}
