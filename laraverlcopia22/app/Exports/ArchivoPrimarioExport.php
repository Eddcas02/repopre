<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ArchivoPrimarioExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize
{
    protected $data;
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'Numero de Cuenta',
            'Cheque',
            'Fecha',
            'Beneficiario',
            'Valor'
        ];
    }

    public function registerEvents(): array
    {
        $alphabetRange = range('A','Z');
        $alphabet = $alphabetRange[4];
        $totalRow = count($this->data) + 1;
        $cellRange = 'A1:'.$alphabet.$totalRow;

        return [
            AfterSheet::class => function(AfterSheet $event) use($cellRange){
                $event->sheet->getStyle($cellRange)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['argb' => '000000'],
                            ],
                        ],
                    ],
                );
            },
        ];
    }
}