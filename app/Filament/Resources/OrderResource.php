<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationGroup = 'Orders';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    public static function form(Form $form): Form
    {
        // Return null form as requested
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                TextColumn::make('customer_email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('payment_reference')
                    ->label('Payment Reference')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('INR')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'manual_payment' => 'Manual Payment',
                        'credit_card' => 'Credit Card',
                        'paypal' => 'PayPal',
                        'bank_transfer' => 'Bank Transfer',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'manual_payment' => 'warning',
                        'credit_card' => 'success',
                        'paypal' => 'info',
                        'bank_transfer' => 'primary',
                        default => 'gray',
                    })
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                TextColumn::make('payment_reference')
                    ->label('Payment Ref')
                    ->limit(20)
                    ->tooltip(fn(?string $state): ?string => $state)
                    ->placeholder('—')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                TextColumn::make('created_at')
                    ->label('Order Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->groups([
                Group::make('created_at')
                    ->label('Order Date')
                    ->date(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
                SelectFilter::make('payment_method')
                    ->options([
                        'manual_payment' => 'Manual Payment',
                        'credit_card' => 'Credit Card',
                        'paypal' => 'PayPal',
                        'bank_transfer' => 'Bank Transfer',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-s-arrow-up')
                    ->color('primary')
                    ->form([
                        Select::make('payment_status')
                            ->label('Payment Status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded',
                            ])
                            ->default(fn(Order $record): string => $record->payment_status)
                            ->required(),
                        Select::make('order_status')
                            ->label('Order Status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                                'refunded' => 'Refunded',
                            ])
                            ->default(fn(Order $record): string => $record->status)
                            ->required(),
                    ])
                    ->modalWidth('sm')
                    ->modalHeading(fn(Order $record): string => "Update Status - Order #{$record->order_number}")
                    ->modalDescription(fn(Order $record): string =>
                        'Update the payment and order status for this order.'
                        . ($record->payment_reference ? " (Payment Reference: {$record->payment_reference})" : ''))
                    ->modalSubmitActionLabel('Update Status')
                    ->action(function (Order $record, array $data): void {
                        $oldPaymentStatus = $record->payment_status;
                        $oldOrderStatus = $record->status;

                        $record->update([
                            'payment_status' => $data['payment_status'],
                            'status' => $data['order_status'],
                        ]);

                        $statusChanges = [];
                        if ($oldPaymentStatus !== $data['payment_status']) {
                            $statusChanges[] = "Payment: {$oldPaymentStatus} → {$data['payment_status']}";
                        }
                        if ($oldOrderStatus !== $data['order_status']) {
                            $statusChanges[] = "Order: {$oldOrderStatus} → {$data['order_status']}";
                        }

                        $changeDescription = empty($statusChanges) ? 'No changes made' : implode(', ', $statusChanges);

                        Notification::make()
                            ->title('Status Updated')
                            ->body("Order #{$record->order_number} updated. {$changeDescription}"
                                . ($data['payment_status'] === 'paid' && $oldPaymentStatus !== 'paid' ? ' Customer will be notified via email.' : ''))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('quick_confirm')
                    ->label('Quick Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Order $record): bool =>
                        $record->payment_status === 'pending' &&
                        $record->payment_method === 'manual_payment')
                    ->requiresConfirmation()
                    ->modalHeading('Quick Confirm Payment')
                    ->modalDescription(fn(Order $record): string =>
                        "Quickly confirm payment and set order to processing for #{$record->order_number}?"
                        . ($record->payment_reference ? " (Reference: {$record->payment_reference})" : ''))
                    ->modalSubmitActionLabel('Confirm & Process')
                    ->action(function (Order $record): void {
                        $record->update([
                            'payment_status' => 'paid',
                            'status' => 'processing',
                        ]);

                        Notification::make()
                            ->title('Payment Confirmed')
                            ->body("Payment confirmed for order #{$record->order_number}. Order set to processing. Customer will be notified via email.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('print_sticker')
                    ->label('Print Sticker')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn(Order $record): string => route('sticker.print', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulk_print_stickers')
                        ->label('Print Stickers')
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Print Stickers')
                        ->modalDescription(fn(Collection $records): string =>
                            "Print shipping stickers for {$records->count()} selected order(s)?")
                        ->modalSubmitActionLabel('Print Stickers')
                        ->action(function (Collection $records): void {
                            $orderIds = $records->pluck('id')->toArray();
                            $url = route('sticker.bulk-print', ['orders' => implode(',', $orderIds)]);

                            Notification::make()
                                ->title('Printing Stickers')
                                ->body("Opening print dialog for {$records->count()} sticker(s). Check your browser for the new tab.")
                                ->info()
                                ->send();

                            // Redirect to bulk print URL
                            redirect($url);
                        }),
                    Tables\Actions\BulkAction::make('bulk_update_status')
                        ->label('Update Status')
                        ->icon('heroicon-o-pencil-square')
                        ->color('primary')
                        ->form([
                            Select::make('payment_status')
                                ->label('Payment Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'paid' => 'Paid',
                                    'failed' => 'Failed',
                                    'refunded' => 'Refunded',
                                ])
                                ->placeholder('Keep current status'),
                            Select::make('order_status')
                                ->label('Order Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'processing' => 'Processing',
                                    'shipped' => 'Shipped',
                                    'delivered' => 'Delivered',
                                    'cancelled' => 'Cancelled',
                                    'refunded' => 'Refunded',
                                ])
                                ->placeholder('Keep current status'),
                        ])
                        ->modalHeading('Bulk Update Status')
                        ->modalDescription(function (Collection $records): string {
                            $count = $records->count();
                            return "Update the status for {$count} selected order(s). Leave fields empty to keep current values.";
                        })
                        ->modalSubmitActionLabel('Update Status')
                        ->action(function (Collection $records, array $data): void {
                            $updatedCount = 0;
                            $emailNotifications = 0;

                            foreach ($records as $record) {
                                $updates = [];
                                $oldPaymentStatus = $record->payment_status;

                                if (!empty($data['payment_status'])) {
                                    $updates['payment_status'] = $data['payment_status'];
                                }
                                if (!empty($data['order_status'])) {
                                    $updates['status'] = $data['order_status'];
                                }

                                if (!empty($updates)) {
                                    $record->update($updates);
                                    $updatedCount++;

                                    // Count email notifications (when payment status changes to paid)
                                    if (isset($updates['payment_status']) &&
                                            $updates['payment_status'] === 'paid' &&
                                            $oldPaymentStatus !== 'paid') {
                                        $emailNotifications++;
                                    }
                                }
                            }

                            if ($updatedCount > 0) {
                                $message = "Successfully updated {$updatedCount} order(s).";
                                if ($emailNotifications > 0) {
                                    $message .= " {$emailNotifications} customer(s) will be notified via email.";
                                }

                                Notification::make()
                                    ->title('Status Updated')
                                    ->body($message)
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('No Updates')
                                    ->body('No status changes were made.')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('quick_confirm_payments')
                        ->label('Quick Confirm Payments')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Quick Confirm Payments')
                        ->modalDescription(function (Collection $records): string {
                            $pendingCount = $records
                                ->where('payment_status', 'pending')
                                ->where('payment_method', 'manual_payment')
                                ->count();

                            if ($pendingCount === 0) {
                                return 'No orders with pending manual payments selected.';
                            }

                            return "Quickly confirm payments and set to processing for {$pendingCount} order(s) with pending manual payments?";
                        })
                        ->modalSubmitActionLabel('Confirm & Process')
                        ->action(function (Collection $records): void {
                            $confirmedCount = 0;

                            foreach ($records as $record) {
                                if ($record->payment_status === 'pending' && $record->payment_method === 'manual_payment') {
                                    $record->update([
                                        'payment_status' => 'paid',
                                        'status' => 'processing',
                                    ]);
                                    $confirmedCount++;
                                }
                            }

                            if ($confirmedCount > 0) {
                                Notification::make()
                                    ->title('Payments Confirmed')
                                    ->body("Successfully confirmed and set to processing {$confirmedCount} order(s). Customers will be notified via email.")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('No Payments Confirmed')
                                    ->body('No orders with pending manual payments were found in the selection.')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
