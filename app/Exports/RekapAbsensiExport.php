<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;

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

            'a.jam_masuk',

            'a.status_masuk',

            'a.jam_pulang',

            'a.status_pulang'

        );



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

            'Jam Masuk',

            'Status Masuk',

            'Jam Pulang',

            'Status Pulang'

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
                'A1:H1'
            );


            $sheet->setCellValue(

                'A1',

                'REKAP ABSENSI SISWA'

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

                'A3:H3'

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

                'A3:H1000'

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