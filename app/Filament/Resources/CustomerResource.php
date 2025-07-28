<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationGroup = 'Customer';
    protected static ?string $navigationLabel = 'Customers';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->maxLength(500),
                \Filament\Forms\Components\Radio::make('payment_type')
                    ->label('Payment')
                    ->options([
                        'to_pay' => 'To pay',
                        'to_receive' => 'To receive',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('balance')
                    ->label('Balance Amount')
                    ->numeric()
                    ->required()
                    ->placeholder('Enter balance amount'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->limit(30),
                Tables\Columns\TextColumn::make('payment_type')->label('Payment')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'to_pay' => 'To Pay',
                        'to_receive' => 'To Receive',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('balance')->label('Opening Balance'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
