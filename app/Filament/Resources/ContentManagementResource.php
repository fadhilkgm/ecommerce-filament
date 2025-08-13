<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContentManagementResource\Pages;
use App\Models\ContentManagement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContentManagementResource extends Resource
{
    protected static ?string $model = ContentManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'Content Management';
    
    protected static ?string $modelLabel = 'Content';
    
    protected static ?string $pluralModelLabel = 'Content Management';

    protected static ?string $navigationGroup = 'Content Management';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Use codes like BANNER_IMAGES, PRIVACY_POLICY, TERMS_CONDITIONS, etc.'),
                        
                        Forms\Components\Select::make('type')
                            ->options([
                                'banner' => 'Banner',
                                'content' => 'Content',
                                'image' => 'Image',
                                'text' => 'Text',
                            ])
                            ->required()
                            ->reactive(),
                        
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                        
                        Forms\Components\Toggle::make('is_enabled')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Content')
                    ->schema([
                        Forms\Components\RichEditor::make('content')
                            ->visible(fn ($get) => in_array($get('type'), ['content', 'text']))
                            ->columnSpanFull(),
                        
                        Forms\Components\FileUpload::make('images')
                            ->multiple()
                            ->image()
                            ->directory('content-management')
                            ->visible(fn ($get) => in_array($get('type'), ['banner', 'image']))
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('link_url')
                            ->url()
                            ->visible(fn ($get) => $get('type') === 'banner')
                            ->helperText('URL to redirect when banner is clicked'),
                    ]),

                Forms\Components\Section::make('Additional Settings')
                    ->schema([
                        Forms\Components\KeyValue::make('meta_data')
                            ->keyLabel('Setting Key')
                            ->valueLabel('Setting Value')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'banner' => 'success',
                        'content' => 'info',
                        'image' => 'warning',
                        'text' => 'gray',
                    }),
                
                Tables\Columns\IconColumn::make('is_enabled')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'banner' => 'Banner',
                        'content' => 'Content',
                        'image' => 'Image',
                        'text' => 'Text',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_enabled')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Enabled')
                    ->falseLabel('Disabled'),
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
            ->defaultSort('sort_order');
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
            'index' => Pages\ListContentManagement::route('/'),
            'create' => Pages\CreateContentManagement::route('/create'),
            'edit' => Pages\EditContentManagement::route('/{record}/edit'),
        ];
    }
}
