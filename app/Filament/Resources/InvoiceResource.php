<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document';
    
    protected static ?int $navigationSort = -100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->searchable()
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('customer_phone')
                    ->searchable()
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('total_discount')
                    ->searchable()
                    ->suffix('%')
                    ->badge()
                    ->color('success')
                    ->default('-'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->searchable()->money('INR')->summarize([
                        Sum::make('total_amount')->money('INR')
                    ]),
                Tables\Columns\IconColumn::make('status')
                    ->label('Status')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->url(fn(Invoice $record): string => route('download.invoice', ['invoice' => $record]))
                    ->icon('heroicon-o-printer')
                    ->openUrlInNewTab(),
            ])->defaultSort('created_at', 'desc')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            // 'view' => Pages\ViewShop::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
