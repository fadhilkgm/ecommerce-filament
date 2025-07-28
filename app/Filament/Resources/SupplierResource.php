<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\RelationManagers;
use App\Models\Supplier;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;
    protected static ?string $navigationGroup = 'Suppliers';
    protected static ?string $navigationLabel = 'Suppliers';
    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name'),
                TextInput::make('email')->email(),
                TextInput::make('phone')->tel()->required(),
                Textarea::make('address'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('address'),
                Tables\Columns\TextColumn::make('credit')
                    ->getStateUsing(fn(Supplier $record): string => $record->getCredit())
                    ->money('INR')
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('debit')
                    ->getStateUsing(fn(Supplier $record): string => $record->getDebit())
                    ->money('INR')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('balance')
                    ->getStateUsing(fn($record)=>$record->getBalance())
                    ->money('INR')
                    ->badge()
                    ->color(function (Supplier $record): string {
                        $balance = $record->getBalance();

                        if ($balance > 0) {
                            return 'success';
                        } elseif ($balance < 0) {
                            return 'danger';
                        } else {
                            return 'primary';
                        }
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('name'),
                TextEntry::make('email'),
                TextEntry::make('phone'),
                TextEntry::make('address'),
                TextEntry::make('credit')
                    ->getStateUsing(fn(Supplier $record): string => $record->getCredit())
                    ->money('INR')
                    ->badge()
                    ->color('danger'),
                TextEntry::make('debit')
                    ->getStateUsing(fn(Supplier $record): string => $record->getDebit())
                    ->money('INR')
                    ->badge()
                    ->color('success'),
                TextEntry::make('balance')
                    ->getStateUsing(function (Supplier $record): string {
                        $balance = $record->getBalance();

                        if ($balance > 0) {
                            return '+' . number_format($balance, 2);
                        } elseif ($balance < 0) {
                            return '-' . number_format(abs($balance), 2);
                        } else {
                            return number_format($balance, 2);
                        }
                    })
                    ->money('INR')
                    ->badge()
                    ->color(function (Supplier $record): string {
                        $balance = $record->getBalance();

                        if ($balance > 0) {
                            return 'success';
                        } elseif ($balance < 0) {
                            return 'danger';
                        } else {
                            return 'primary';
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSuppliers::route('/'),
        ];
    }
}
