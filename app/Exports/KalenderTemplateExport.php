<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Export ini membuat template import kalender sekolah dengan beberapa sheet panduan.
class KalenderTemplateExport implements WithMultipleSheets
{
    public function __construct(
        private array $rows,
        private array $tahunAjaran,
        private array $provinsi
    ) {}

    // Template terdiri dari sheet isian, sheet panduan, dan sheet referensi.
    public function sheets(): array
    {
        return [
            new KalenderTemplateDataSheet($this->rows, $this->tahunAjaran, $this->provinsi),
            new KalenderTemplateGuideSheet,
            new KalenderTemplateReferenceSheet($this->tahunAjaran, $this->provinsi),
        ];
    }
}

// Sheet utama tempat admin mengisi data kalender yang akan diimport.
class KalenderTemplateDataSheet implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithTitle
{
    public function __construct(
        private array $rows,
        private array $tahunAjaran,
        private array $provinsi
    ) {}

    // Nama sheet ini muncul di tab bawah file Excel.
    public function title(): string
    {
        return 'Isi Kalender';
    }

    // Header memakai nama field agar mudah dipetakan saat proses import.
    public function headings(): array
    {
        return ['tahun_ajaran', 'semester', 'tanggal_mulai', 'tanggal_selesai', 'judul', 'jenis', 'provinsi', 'keterangan'];
    }

    // Baris contoh dikirim ke sheet agar admin tahu format pengisian.
    public function array(): array
    {
        return $this->rows;
    }

    // Event ini mengatur tampilan sheet dan dropdown pilihan agar input admin lebih rapi.
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:H1');
                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getStyle('A1:H1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '273C75']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $sheet->getStyle('A1:H200')->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9E2F3']]],
                ]);
                $sheet->getStyle('C:D')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
                $sheet->getStyle('H:H')->getAlignment()->setWrapText(true);

                $this->setDropdown($sheet, 'B2:B200', ['ganjil', 'genap']);
                $this->setDropdown($sheet, 'F2:F200', ['libur', 'kegiatan', 'ujian']);
                $this->setDropdown($sheet, 'G2:G200', $this->provinsi);
            },
        ];
    }

    // Membuat dropdown Excel untuk kolom yang hanya boleh berisi pilihan tertentu.
    private function setDropdown($sheet, string $range, array $items): void
    {
        $validation = $sheet->getCell(explode(':', $range)[0])->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1('"'.implode(',', array_slice($items, 0, 80)).'"');

        foreach ($sheet->rangeToArray($range) as $rowIndex => $_) {
            $cell = preg_replace('/\d+/', '', explode(':', $range)[0]).($rowIndex + 2);
            $sheet->getCell($cell)->setDataValidation(clone $validation);
        }
    }
}

// Sheet panduan menjelaskan aturan pengisian template import kalender.
class KalenderTemplateGuideSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function title(): string
    {
        return 'Panduan';
    }

    public function array(): array
    {
        return [
            ['Panduan Import Kalender Pendidikan'],
            ['1. Isi data hanya di sheet "Isi Kalender".'],
            ['2. Format tanggal wajib yyyy-mm-dd, contoh 2026-07-01.'],
            ['3. Jenis hanya boleh: libur, kegiatan, ujian.'],
            ['4. Gunakan provinsi Nasional untuk tanggal merah nasional.'],
            ['5. Hari libur tidak akan dihitung alfa pada rekap absensi.'],
            ['6. Jika tahun_ajaran/semester kosong, data dianggap umum.'],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->mergeCells('A1:D1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1:D7')->getAlignment()->setWrapText(true);
            },
        ];
    }
}

class KalenderTemplateReferenceSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(
        private array $tahunAjaran,
        private array $provinsi
    ) {}

    public function title(): string
    {
        return 'Referensi';
    }

    public function array(): array
    {
        $rows = [['Tahun Ajaran', 'Semester', 'Provinsi']];
        $max = max(count($this->tahunAjaran), count($this->provinsi));

        for ($i = 0; $i < $max; $i++) {
            $ta = $this->tahunAjaran[$i] ?? null;
            $rows[] = [
                $ta['nama'] ?? '',
                $ta['semester'] ?? '',
                $this->provinsi[$i] ?? '',
            ];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1:C1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '16A34A']],
                ]);
            },
        ];
    }
}
