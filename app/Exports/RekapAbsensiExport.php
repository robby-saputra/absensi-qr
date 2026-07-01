<?php

namespace App\Exports;

use App\Services\AttendanceSettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

// Export ini membuat laporan rekap absensi harian dalam bentuk Excel.
class RekapAbsensiExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings
{
    private $filters;

    // Filter dari halaman rekap disimpan agar isi export sama dengan data yang dilihat admin.
    public function __construct(
        $filters
    ) {

        $this->filters =
        $filters;

    }

    // Mengambil data absensi, siswa, kelas, dan status agar bisa disusun menjadi laporan Excel.
    public function collection()
    {

        $query = DB::table(
            'absensis as a'
        )
            ->join(
                'users as s',
                's.id',
                '=',
                'a.id_siswa'
            )
            ->leftJoin(
                'kelas as k',
                'k.id',
                '=',
                's.kelas_id'
            )
            ->select(

                'a.tanggal',

                's.nama',

                's.nis',

                'k.nama_kelas',

                DB::raw("
                CASE
                    WHEN a.status_masuk IN ('izin','sakit') THEN a.status_masuk
                    WHEN a.status_pulang IN ('izin','sakit') THEN a.status_pulang
                    WHEN a.status_masuk IS NOT NULL THEN CONCAT(COALESCE(a.jam_masuk, ''), ' - ', a.status_masuk)
                    WHEN a.jam_masuk IS NOT NULL THEN a.jam_masuk
                    ELSE '-'
                END as absen_harian_masuk
            "),

                DB::raw("
                CASE
                    WHEN a.status_masuk IN ('izin','sakit') THEN a.status_masuk
                    WHEN a.status_pulang IN ('izin','sakit') THEN a.status_pulang
                    WHEN a.status_pulang IS NOT NULL THEN CONCAT(COALESCE(a.jam_pulang, ''), ' - ', a.status_pulang)
                    WHEN a.jam_pulang IS NOT NULL THEN a.jam_pulang
                    ELSE '-'
                END as absen_harian_pulang
            "),

                DB::raw("
                CASE
                    WHEN a.status_masuk IN ('izin','sakit') THEN a.status_masuk
                    WHEN a.status_pulang IN ('izin','sakit') THEN a.status_pulang
                    WHEN a.status_masuk IN ('telat','terlambat') THEN 'telat'
                    WHEN a.status_masuk IS NOT NULL THEN 'hadir'
                    ELSE '".AttendanceSettingService::statusDefaultAlfa()."'
                END as status_siswa
            ")

            );

        if (Schema::hasColumn('absensis', 'deleted_at')) {
            $query->whereNull('a.deleted_at');
        }

        if (! empty($this->filters['tahun_ajaran_id'])) {
            $tahunAjaran = DB::table('tahun_ajarans')->where('id', $this->filters['tahun_ajaran_id'])->first();
            $query->where(function ($tahun) use ($tahunAjaran) {
                $tahun->where('a.tahun_ajaran_id', $this->filters['tahun_ajaran_id']);
                if ($tahunAjaran) {
                    $tahun->orWhere(fn ($legacy) => $legacy->whereNull('a.tahun_ajaran_id')->whereDate('a.tanggal', '>=', $tahunAjaran->tanggal_mulai)->whereDate('a.tanggal', '<=', $tahunAjaran->tanggal_selesai));
                }
            });
        }

        if (! empty($this->filters['kelas_id'])) {
            $query->where('s.kelas_id', $this->filters['kelas_id']);
        }
        if (! empty($this->filters['search'])) {
            $query->where(fn ($search) => $search->where('s.nama', 'like', '%'.$this->filters['search'].'%')->orWhere('s.nis', 'like', '%'.$this->filters['search'].'%'));
        }
        if (! empty($this->filters['status'])) {
            match ($this->filters['status']) {
                'hadir' => $query->whereNotNull('a.status_masuk')->whereNotIn('a.status_masuk', ['izin', 'sakit', 'alfa', 'telat', 'terlambat'])->whereNotIn('a.status_pulang', ['izin', 'sakit']),
                'telat' => $query->whereIn('a.status_masuk', ['telat', 'terlambat']),
                'izin' => $query->where(fn ($status) => $status->where('a.status_masuk', 'izin')->orWhere('a.status_pulang', 'izin')),
                'sakit' => $query->where(fn ($status) => $status->where('a.status_masuk', 'sakit')->orWhere('a.status_pulang', 'sakit')),
                'alfa' => $query->where(fn ($status) => $status->where('a.status_masuk', 'alfa')->orWhereNull('a.status_masuk')),
                default => null,
            };
        }

        if (

            $this->filters['mode']

            ==

            'bulan'

        ) {

            $query
                ->whereYear(

                    'a.tanggal',

                    substr(

                        $this->filters['bulan'],

                        0,

                        4

                    )

                )
                ->whereMonth(

                    'a.tanggal',

                    substr(

                        $this->filters['bulan'],

                        5,

                        2

                    )

                );

        } else {

            $query
                ->whereDate(

                    'a.tanggal',

                    $this->filters['tanggal']

                );

        }

        return $query
            ->orderByDesc(

                'a.tanggal'

            )
            ->orderBy(

                's.nama'

            )
            ->get();

    }

    public function headings(): array
    {

        return [

            'Tanggal',

            'Nama',

            'NIS',

            'Kelas',

            'Absen Harian Masuk',

            'Absen Harian Pulang',
            'Status Siswa',

        ];

    }

    public function registerEvents(): array
    {

        return [

            AfterSheet::class => function (

                AfterSheet $event

            ) {

                $sheet =

                $event->sheet
                    ->getDelegate();

                /*
            JUDUL
            */

                $sheet->insertNewRowBefore(
                    1,
                    2
                );

                $sheet->mergeCells(
                    'A1:G1'
                );

                $sheet->setCellValue(

                    'A1',

                    'REKAP ABSENSI HARIAN SISWA'

                );

                $sheet->getStyle(

                    'A1'

                )
                    ->getFont()
                    ->setBold(

                        true

                    )
                    ->setSize(

                        18

                    );

                /*
            HEADER
            */

                $sheet->getStyle(

                    'A3:G3'

                )
                    ->applyFromArray([

                        'font' => [

                            'bold' => true,

                            'color' => [

                                'rgb' => 'FFFFFF',

                            ],

                        ],

                        'fill' => [

                            'fillType' => 'solid',

                            'startColor' => [

                                'rgb' => '273C75',

                            ],

                        ],

                    ]);

                /*
            BORDER
            */

                $sheet->getStyle(

                    'A3:G1000'

                )
                    ->applyFromArray([

                        'borders' => [

                            'allBorders' => [

                                'borderStyle' => 'thin',

                            ],

                        ],

                    ]);

            },

        ];

    }
}
