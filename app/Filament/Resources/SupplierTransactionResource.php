<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierTransactionResource\Pages;
use App\Filament\Resources\SupplierTransactionResource\RelationManagers;
use App\Models\SupplierTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplierTransactionResource extends Resource
{
    protected static ?string $model = SupplierTransaction::class;
    protected static ?string $navigationGroup = 'Suppliers';
    protected static ?int $navigationSort = 8;
    protected static ?string $label = 'Supplier Transactions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->required()
                    ->relationship('user', 'name')->searchable()->preload(),
                Forms\Components\Select::make('supplier_id')
                    ->required()
                    ->relationship('supplier', 'name')->searchable()->preload(),
                Forms\Components\TextInput::make('transaction_number')
                    ->maxLength(255)
                    ->default(fn(): string => sprintf('TRX-%s', (int) SupplierTransaction::latest()->value('transaction_number') + 1)),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('type')->options([
                    'debit' => 'Debit',
                    'credit' => 'Credit',
                ])
                    ->required(),
                Forms\Components\Select::make('payment_method')->options([
                    'cash' => 'Cash',
                    'credit' => 'Credit',
                ])
                    ->required(),
                Forms\Components\DatePicker::make('date')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')->badge()->color(fn($state): string => match ($state) {
                    'debit' => 'success',
                    'credit' => 'danger',
                }),
                Tables\Columns\TextColumn::make('payment_method'),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('supplier_id')->relationship('supplier', 'name')->searchable()->preload(),
                SelectFilter::make('type')->options([
                    'debit' => 'Debit',
                    'credit' => 'Credit',
                ])
            ], FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ManageSupplierTransactions::route('/'),
        ];
    }
}
