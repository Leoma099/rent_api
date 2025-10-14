<?php

namespace App\Imports;

use App\Models\Equipment;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log; // ✅ Import Log correctly

class EquipmentImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        Log::info('CSV Row:', $row);

        return new Equipment([
            'id' => $row['id'] ?? null,
            'property_number' => $row['property_number'] ?? 'MISSING',
            'serial_number' => $row['serial_number'] ?? 'MISSING',
            'type' => $row['type'] ?? 'MISSING',
            'brand' => $row['brand'] ?? 'MISSING',
            'model' => $row['model'] ?? 'MISSING',
            'equipmentStatus' => $row['status'] ?? 'MISSING',
        ]);
    }
}

