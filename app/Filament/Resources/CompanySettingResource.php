<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanySettingResource\Pages;
use App\Models\CompanySetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class CompanySettingResource extends Resource
{
    protected static ?string $model = CompanySetting::class;
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 10;
    // protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Company Settings';
    protected static ?string $modelLabel = 'Company Setting';
    protected static ?string $pluralModelLabel = 'Company Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('key')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->helperText('Unique identifier for this setting (e.g., company_name, company_address)'),
                
                Forms\Components\Select::make('type')
                    ->required()
                    ->options([
                        'string' => 'Text',
                        'text' => 'Long Text',
                        'boolean' => 'Yes/No',
                        'integer' => 'Number (Integer)',
                        'decimal' => 'Number (Decimal)',
                    ])
                    ->default('string')
                    ->live()
                    ->helperText('Data type for proper value handling'),
                
                Forms\Components\TextInput::make('value')
                    ->label('Value')
                    ->visible(fn (Forms\Get $get): bool => in_array($get('type'), ['string', 'integer', 'decimal']))
                    ->helperText(fn (Forms\Get $get): string => match ($get('type')) {
                        'integer' => 'Enter a whole number',
                        'decimal' => 'Enter a decimal number',
                        default => 'Enter text value',
                    }),
                
                Forms\Components\Textarea::make('value')
                    ->label('Value')
                    ->visible(fn (Forms\Get $get): bool => $get('type') === 'text')
                    ->rows(6)
                    ->helperText('Enter long text content (for addresses, use multiple lines)'),
                
                Forms\Components\Toggle::make('boolean_value')
                    ->label('Value')
                    ->visible(fn (Forms\Get $get): bool => $get('type') === 'boolean')
                    ->helperText('Toggle on/off')
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $set('value', $state ? '1' : '0');
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Setting Key')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'boolean' => 'success',
                        'integer', 'decimal' => 'warning',
                        'text' => 'info',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('value')
                    ->label('Value')
                    ->limit(50)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->formatStateUsing(function ($state, $record) {
                        return match ($record->type) {
                            'boolean' => $state === '1' ? '✅ Yes' : '❌ No',
                            'text' => 'Long Text...',
                            default => $state,
                        };
                    }),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'string' => 'Text',
                        'text' => 'Long Text',
                        'boolean' => 'Yes/No',
                        'integer' => 'Number (Integer)',
                        'decimal' => 'Number (Decimal)',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('key');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanySettings::route('/'),
            'create' => Pages\CreateCompanySetting::route('/create'),
            'edit' => Pages\EditCompanySetting::route('/{record}/edit'),
        ];
    }
}
