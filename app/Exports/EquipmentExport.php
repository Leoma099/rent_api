<?php

namespace App\Exports;

use App\Models\Equipment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class EquipmentExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return Equipment::select(
            'property_number',
            'serial_number',
            'type',
            'brand',
            'model',
            'equipmentStatus',
        )->get();
    }

    public function headings(): array
    {
        return [
            'Property Number',
            'Serial Number',
            'Type',
            'Brand',
            'Model',
            'Status'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Set bold for heading row
                $event->sheet->getStyle('A1:H1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                ]);

                // Set column widths
                $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(30);
                foreach (range('B', 'H') as $col) {
                    $event->sheet->getDelegate()->getColumnDimension($col)->setWidth(25);
                }
            },
        ];
    }
}
