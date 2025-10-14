<?php

namespace App\Imports;

use App\Models\Borrow;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class BorrowImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        Log::info('XLSX Row:', $row);

        // Check if date_borrow exists in the row, otherwise set to default value
        $dateBorrow = isset($row['date_borrow']) ? $this->parseDate($row['date_borrow']) : now()->format('Y-m-d');
        $dateReturn = isset($row['date_return']) ? $this->parseDate($row['date_return']) : null;

        return new Borrow([
            'id' => $row['id'] ?? null,
            'account_id' => $row['account_id'] ?? null,
            'equipment_id' => $row['equipment_id'] ?? null,

            'full_name' => $row['borrower_name'] ?? 'MISSING',
            'id_number' => $row['id_number'] ?? 'MISSING',
            'office_name' => $row['department'] ?? 'MISSING',
            'office_address' => $row['department_address'] ?? 'MISSING',
            'type' => $row['equipment_type'] ?? 'MISSING',
            'brand' => $row['brand'] ?? 'MISSING',
            'model' => $row['model'] ?? 'MISSING',
            'property_number' => $row['property_number'] ?? 'MISSING',
            'position' => $row['position'] ?? 'MISSING',
            'mobile_number' => $row['contact'] ?? 'MISSING',
            'status' => $row['status'] ?? 0,
            'purpose' => $row['purpose'] ?? 'MISSING',
            'date_borrow' => $dateBorrow, // Ensure it has a valid value
            'date_return' => $dateReturn, // Handle missing or invalid date_return
        ]);
    }

    private function parseDate($date)
    {
        // If the date is in an invalid format or empty, return current date
        if (empty($date)) {
            return now()->format('Y-m-d');
        }

        // Attempt to parse the date
        $parsedDate = \DateTime::createFromFormat('Y-m-d', $date);
        return $parsedDate ? $parsedDate->format('Y-m-d') : now()->format('Y-m-d');
    }
}
