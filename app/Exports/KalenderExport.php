<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Export ini membuat file Excel berisi data kalender sekolah yang sudah tersimpan.
class KalenderExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings
{
    public function __construct(private Collection $rows) {}

    // Header kolom Excel disesuaikan dengan field kalender sekolah.
    public function headings(): array
    {
        return ['Tanggal Mulai', 'Tanggal Selesai', 'Judul', 'Jenis', 'Provinsi', 'Sumber', 'Berulang', 'Hari Berulang', 'Keterangan'];
    }

    // Mengubah collection dari database menjadi baris-baris Excel yang mudah dibaca.
    public function collection(): Collection
    {
        return $this->rows->map(fn ($row) => [
            $row->tanggal_mulai,
            $row->tanggal_selesai,
            $row->judul,
            $row->jenis,
            $row->provinsi ?? 'Umum',
            $row->sumber ?? 'manual',
            ($row->berulang ?? false) ? 'Ya' : 'Tidak',
            $row->hari_berulang ?? '-',
            $row->keterangan ?? '-',
        ]);
    }

    // Event ini memberi judul dan warna header setelah sheet Excel selesai dibuat.
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->insertNewRowBefore(1, 2);
                $sheet->mergeCells('A1:I1');
                $sheet->setCellValue('A1', 'EXPORT KALENDER SEKOLAH');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A3:I3')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '273C75']],
                ]);
            },
        ];
    }
}
