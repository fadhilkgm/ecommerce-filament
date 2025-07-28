<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected $originalBalance;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Capture original balance before save
        $this->originalBalance = (float) ($this->record->balance ?? 0);
        return $data;
    }

    protected function afterSave(): void
    {
        $customer = $this->record;
        $originalBalance = $this->originalBalance ?? 0;
        $currentBalance = (float) $customer->balance;

        // Only process if balance changed
        if ($currentBalance != $originalBalance) {
            $transactionType = $customer->payment_type === 'to_pay' ? 'credit' : 'debit';
            $transactionTypeCategory = $customer->payment_type === 'to_pay' ? 'sales' : 'expense';
            
            if ($currentBalance > 0) {
                // Create or update transaction when balance > 0
                \App\Models\Transaction::updateOrCreate(
                    [
                        'shop_id' => $customer->shop_id,
                        'transaction_number' => 'CUOP-000' . $customer->id,
                    ],
                    [
                        'amount' => $currentBalance,
                        'type' => $transactionType,
                        'transaction_type' => $transactionTypeCategory,
                        'date' => now()->toDateString(),
                        'user_id' => auth()->user()->id,
                        'transaction_comment' => 'Opening balance for customer: ' . $customer->name,
                    ]
                );
            } else {
                // Delete transaction when balance becomes 0 or negative
                \App\Models\Transaction::where('shop_id', $customer->shop_id)
                    ->where('transaction_number', 'CUOP-000' . $customer->id)
                    ->delete();
            }
        }
    }
}
