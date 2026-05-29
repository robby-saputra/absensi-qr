<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use App\Services\AttendanceSettingService;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;

use Maatwebsite\Excel\Events\AfterSheet;


class RekapAbsensiExport

implements

FromCollection,

WithHeadings,

ShouldAutoSize,

WithEvents

{

    private $filters;


    public function __construct(
        $filters
    ){

        $this->filters =
        $filters;

    }



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

        if (! empty($this->filters['tahun_ajaran_id'])) {
            $query->where('a.tahun_ajaran_id', $this->filters['tahun_ajaran_id']);
        }



        if(

            $this->filters['mode']

            ==

            'bulan'

        ){

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

        }

        else{

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




    public function headings():array
    {

        return [

            'Tanggal',

            'Nama',

            'NIS',

            'Kelas',

            'Absen Harian Masuk',

            'Absen Harian Pulang'

            ,
            'Status Siswa'

        ];

    }




    public function registerEvents():array
    {

        return [

        AfterSheet::class =>

        function(

            AfterSheet $event

        ){

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

                'font'=>[

                    'bold'=>true,

                    'color'=>[

                        'rgb'=>'FFFFFF'

                    ]

                ],


                'fill'=>[

                    'fillType'=>'solid',

                    'startColor'=>[

                        'rgb'=>'273C75'

                    ]

                ]

            ]);



            /*
            BORDER
            */

            $sheet->getStyle(

                'A3:G1000'

            )

            ->applyFromArray([

                'borders'=>[

                    'allBorders'=>[

                        'borderStyle'=>'thin'

                    ]

                ]

            ]);


        }

        ];

    }


}
