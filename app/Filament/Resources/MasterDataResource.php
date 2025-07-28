<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MasterDataResource\Pages;
use App\Models\MasterData;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class MasterDataResource extends Resource
{
    protected static ?string $model = MasterData::class;
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (callable $set, $state) {
                        if ($state) {
                            $set('code', Str::upper(Str::replace(' ', '_', $state)));
                        }
                    }),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->label('Type')
                    ->options([
                        'TXT' => 'Text',
                        'BLN' => 'Boolean',
                        'LST' => 'List',
                        'MLT' => 'Multiple',
                        'LONG_TXT' => 'Long Text',
                    ])
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        if ($state === 'TXT') {
                            $set('repeater_visibility', false);
                        } else {
                            $set('repeater_visibility', true);
                        }
                    }),
                Section::make('Master Data Values')->schema([
                    Forms\Components\TextInput::make('value')
                        ->visible(fn($get) => $get('type') === 'TXT'),
                    Forms\Components\MarkdownEditor::make('value')
                        ->visible(fn($get) => $get('type') === 'LONG_TXT'),
                    Forms\Components\Repeater::make('masterDataValue')
                        ->relationship('values')
                        ->schema([
                            Forms\Components\TextInput::make('description')
                                ->required()->label('Label')->reactive()->debounce(500)->live(onBlur: true)->afterStateUpdated(fn(callable $set, $state) => $set('value', strtoupper($state))),
                            Forms\Components\TextInput::make('value')
                                ->required(),
                        ])
                        ->mutateRelationshipDataBeforeCreateUsing(function ($set, $get, array $data) {
                            $data['master_data_code'] = $get('code');
                            return $data;
                        })->columns(2)
                        ->visible(fn($get) => $get('type') == 'MLT' || $get('type') == 'LST')->addActionLabel('Add More'),
                ])->visible(fn($get) => $get('type') !== 'BLN' && $get('type') !== null)
                    ->reactive()
                    ->live(),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')->copyable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListMasterData::route('/'),
            'create' => Pages\CreateMasterData::route('/create'),
            'view' => Pages\ViewMasterData::route('/{record}'),
            'edit' => Pages\EditMasterData::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
