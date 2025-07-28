<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $customer = $this->record;
        
        if ($customer->balance > 0) {
            // Determine transaction type based on payment_type
            $transactionType = $customer->payment_type === 'to_pay' ? 'credit' : 'debit';
            $transactionTypeCategory = $customer->payment_type === 'to_pay' ? 'sales' : 'expense';
            
            // Create customer transaction
            \App\Models\Transaction::create([
                'shop_id' => $customer->shop_id,
                'transaction_number' => 'CUOP-000' . $customer->id,
                'amount' => $customer->balance,
                'type' => $transactionType,
                'transaction_type' => $transactionTypeCategory,
                'date' => now()->toDateString(),
                'user_id' => auth()->user()->id,
                'transaction_comment' => 'Opening balance for customer: ' . $customer->name,
            ]);
        }
    }
}
