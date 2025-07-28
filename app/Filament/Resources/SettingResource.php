<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort =11;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->is_admin ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->reactive()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $state, Set $set) {
                        $generatedCode = strtoupper(str_replace(' ', '_', $state));
                        $set('code', $generatedCode);
                    }),

                TextInput::make('code')
                    ->required()
                    ->disabled()
                    ->dehydrated(),

                Select::make('type')
                    ->reactive()
                    ->options([
                        'boolean' => 'YES/NO',
                        'text' => 'Text',
                    ])
                    ->required(),

                TextInput::make('value')
                    ->hidden(fn(Get $get): bool => $get('type') !== 'text')
                    ->default('')
                    ->afterStateHydrated(function (?string $state, Set $set) {
                        $set('value', $state ?? '');
                    }),

                Forms\Components\Toggle::make('value')
                    ->hidden(fn(Get $get): bool => $get('type') !== 'boolean')
                    ->default(false)
                    ->afterStateHydrated(function (?bool $state, Set $set) {
                        $set('value', $state ?? false);
                    }),

            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('code'),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\ToggleColumn::make('value')
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
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'view' => Pages\ViewSetting::route('/{record}'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}
