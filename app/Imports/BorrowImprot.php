<?php

namespace App\Imports;

use App\Models\Borrow;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log; // ✅ Import Log correctly

class BorrowImprot implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        Log::info('XLSX Row:', $row);

        return new Borrow([
            'id' => $row['id'] ?? null,
            'full_name' => $row['full_name'] ?? 'MISSING',
            'office_name' => $row['office_name'] ?? 'MISSING',
            'mobile_number' => $row['mobile_number'] ?? 'MISSING',
            'date_borrowed' => $row['date_borrowed'] ?? 'MISSING',
            'date_returned' => $row['date_returned'] ?? 'MISSING',
            'status' => $row['status'] ?? 0,
        ]);
    }
}
