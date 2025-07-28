<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;

class CustomerImport implements ToModel, WithHeadingRow, WithBatchInserts
{
    public function model(array $row)
    {
        return new Customer([
            'name' => $row['name'] ?? 'Unknown',
            'email' => $row['email'] ?? null,
            'phone' => $row['phone'] ?? 'N/A',
            'address' => $row['address'] ?? null,
            'shop_id' => Filament::getTenant()->id,
        ]);
    }

    public function batchSize(): int
    {
        return 100;
    }
}
