<?php

namespace App\Exports;

use App\Models\Borrow;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class BorrowRecordExport implements FromCollection, WithHeadings, WithEvents
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        $query = Borrow::with(['equipment', 'account.user']);

        // Filtering
        if (!empty($this->filters['date_borrow']) && !empty($this->filters['date_return'])) {
            $query->whereBetween('date_borrow', [$this->filters['date_borrow'], $this->filters['date_return']]);
        }

        if (!empty($this->filters['office_name'])) {
            $query->whereHas('account', function ($q) {
                $q->where('office_name', $this->filters['office_name']);
            });
        }

        if (!empty($this->filters['full_name'])) {
            $query->whereHas('account', function ($q) {
                $q->where('full_name', $this->filters['full_name']);
            });
        }

        if (!empty($this->filters['property_number'])) {
            $query->where('property_number', 'LIKE', '%' . $this->filters['property_number'] . '%');
        }

        if (!empty($this->filters['type'])) {
            $query->whereHas('equipment', function ($q) {
                $q->where('type', 'LIKE', '%' . $this->filters['type'] . '%');
            });
        }

        $records = $query->get();

        // Format records for export
        return $records->map(function ($record) {
            return [
                $record->account->id_number ?? 'N/A',
                $record->account->full_name ?? 'N/A',
                $record->account->office_name ?? 'N/A',
                $record->account->position ?? 'N/A',
                $record->account->office_address ?? 'N/A',
                $record->account->mobile_number ?? 'N/A',
                $record->equipment->type ?? 'N/A',
                $record->status ?? 'N/A',
                $record->equipment->brand ?? 'N/A',
                $record->equipment->model ?? 'N/A',
                $record->property_number ?? 'N/A',
                $record->date_borrow,
                $record->date_return,
            ];
        });
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
                $event->sheet->getStyle('A1:M1')->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                foreach (range('A', 'M') as $col) {
                    $event->sheet->getDelegate()->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}
