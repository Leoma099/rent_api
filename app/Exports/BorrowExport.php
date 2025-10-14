<?php

namespace App\Exports;

use App\Models\Borrow;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class BorrowExport implements FromCollection, WithHeadings, WithEvents
{
    public function collection()
    {
        return Borrow::select(
            'id_number',
            'full_name',
            'office_name',
            'position',
            'office_address',
            'mobile_number',
            'type',
            'status',
            'brand',
            'model',
            'property_number',
            'date_borrow',
            'date_return',
        )->get();
    }

    public function headings(): array
    {
        return [
            'ID NUMBER',
            'BORROWER NAME',
            'DEPARTMENT',
            'POSITION',
            'DEPARTMENT ADDRESS',
            'CONTACT #',
            'EQUIPMENT TYPE',
            'STATUS',
            'BRAND',
            'MODEL',
            'PROPERTY NUMBER',
            'DATE BORROW',
            'DATE RETURN',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Set bold for heading row
                $event->sheet->getStyle('A1:M1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                ]);

                // Set column widths
                $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('B')->setWidth(50);
                $event->sheet->getDelegate()->getColumnDimension('C')->setWidth(100);
                $event->sheet->getDelegate()->getColumnDimension('D')->setWidth(25);
                $event->sheet->getDelegate()->getColumnDimension('E')->setWidth(100);
                $event->sheet->getDelegate()->getColumnDimension('F')->setWidth(25);
                $event->sheet->getDelegate()->getColumnDimension('G')->setWidth(30);
                $event->sheet->getDelegate()->getColumnDimension('H')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('I')->setWidth(30);
                $event->sheet->getDelegate()->getColumnDimension('J')->setWidth(30);
                $event->sheet->getDelegate()->getColumnDimension('K')->setWidth(30);
                $event->sheet->getDelegate()->getColumnDimension('L')->setWidth(30);
                $event->sheet->getDelegate()->getColumnDimension('M')->setWidth(30);
            },
        ];
    }
}
