<?php

namespace App\Filament\Resources\SupplierTransactionResource\Pages;

use App\Filament\Resources\SupplierTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSupplierTransactions extends ManageRecords
{
    protected static string $resource = SupplierTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
