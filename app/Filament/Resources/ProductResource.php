<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use Filament\Facades\Filament;
use App\Models\Setting;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationGroup = 'Products';
    protected static ?int $navigationSort = -96;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)->schema([
                    Section::make('General Information')->schema([
                        Select::make('category_id')
                            ->label('Category')
                            ->options(function (callable $get) {
                                $categories = Category::all()->groupBy('parent_id');
                                $options = [];

                                // First process all root categories (parent_id = null)
                                foreach ($categories[null] ?? [] as $parentCategory) {
                                    $options[$parentCategory->id] = $parentCategory->name;

                                    // Process children recursively with increasing depth
                                    self::addChildrenWithDepth($categories, $options, $parentCategory->id);
                                }

                                return $options;
                            })
                            ->suffixAction(
                                Action::make('add-category')
                                    ->label('Add Category')
                                    ->icon('heroicon-o-plus-circle')
                                    ->form([
                                        Select::make('parent_id')
                                            ->options(function (callable $get) {
                                                $categories = Category::all()->groupBy('parent_id');
                                                $options = [];

                                                // First process all root categories (parent_id = null)
                                                foreach ($categories[null] ?? [] as $parentCategory) {
                                                    $options[$parentCategory->id] = $parentCategory->name;

                                                    // Process children recursively with increasing depth
                                                    $this->addChildrenWithDepth($categories, $options, $parentCategory->id);
                                                }
                                                return $options;
                                            })
                                            ->searchable()
                                            ->preload(),
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->action(function ($data) {
                                        $data['shop_id'] = Filament::getTenant()->id;
                                        Category::create($data);
                                    })
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('name')
                            ->label('Product Name')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('price')
                            ->label('Regular Price')
                            ->numeric()
                            ->required(),
                        TextInput::make('cost_price')
                            ->label('Cost Price')
                            ->numeric(),
                        TextInput::make('stock')
                            ->label('Stock')
                            ->numeric()
                            ->visible(function () {
                                $setting = Setting::where('code', 'PRODUCT_PROPERTY')->first();
                                return $setting ? !filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) : true;
                            }),
                    ])->columns(2)->columnSpan(1),
                    Section::make()->schema([
                        Repeater::make('variants')
                            ->label('Product Variants')
                            ->relationship('variants')
                            ->schema([
                                TextInput::make('stock')
                                    ->label('Stock')
                                    ->numeric()
                                    ->required(),
                                TextInput::make('price')
                                    ->label('Price')
                                    ->numeric()
                                    ->required(),
                                    Repeater::make('variantAttributes')
                                    ->label('Product Variations')
                                    ->relationship('attributes')
                                    ->schema([
                                        Select::make('product_attribute_id')
                                            ->label('Attribute')
                                            ->options(ProductAttribute::all()->pluck('name', 'id'))
                                            ->required()
                                            ->default(function ($get) {
                                                // Define the preferred order of attributes
                                                $preferredOrder = ProductAttribute::pluck('name'); // Adjust these names to match your database

                                                // Get all repeater items
                                                $repeaterItems = $get('../../variantAttributes') ?? [];
                                                $currentIndex = count($repeaterItems);

                                                // Get already selected attributes
                                                $selectedAttributes = collect($repeaterItems)
                                                    ->pluck('product_attribute_id')
                                                    ->filter()
                                                    ->toArray();

                                                // Try to assign based on preferred order
                                                foreach ($preferredOrder as $attributeName) {
                                                    $attribute = ProductAttribute::where('name', $attributeName)->first();
                                                    if ($attribute && !in_array($attribute->id, $selectedAttributes)) {
                                                        return $attribute->id;
                                                    }
                                                }

                                                // Fallback to any available attribute
                                                $availableAttribute = ProductAttribute::whereNotIn('id', $selectedAttributes)->first();
                                                return $availableAttribute?->id;
                                            })
                                            ->reactive()
                                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                            ->afterStateUpdated(fn(callable $set) => $set('attribute_value_id', null))
                                            ->columnSpan(1),
                                        Select::make('value')
                                            ->label('Attribute Value')
                                            ->required()
                                            ->options(fn(callable $get) => self::makeValuesOption($get('product_attribute_id')))
                                            ->columnSpan(1),
                                    ])
                                    ->addActionLabel('Add Attribute')
                                    ->columns(2)
                                    ->minItems(1)
                                    ->defaultItems(ProductAttribute::count())
                                    ->addActionAlignment(Alignment::End)
                                    ->maxItems(3)
                                    ->columnSpanFull()
                            ])
                            ->columns(2)
                            ->addActionLabel('Add Variant')
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, Product $product): array {
                                $data['sku'] = Str::slug($product->name) . '-' . random_int(1000, 9999);
                                return $data;
                            })
                            ->visible(function () {
                                $setting = Setting::where('code', 'PRODUCT_PROPERTY')->first();
                                return $setting ? filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) : false;
                            })
                           ,
                    ])->columnSpan(1),
                ])->columns(2),

            ]);
    }

    protected static function addChildrenWithDepth($groupedCategories, &$options, $parentId, $depth = 1)
    {
        foreach ($groupedCategories[$parentId] ?? [] as $childCategory) {
            $options[$childCategory->id] = str_repeat('-', $depth) . ' ' . $childCategory->name;

            // Recursively process grandchildren with increased depth
            self::addChildrenWithDepth($groupedCategories, $options, $childCategory->id, $depth + 1);
        }
    }

    private static function makeValuesOption($attributeId)
    {
        $attribute = ProductAttribute::find($attributeId);
        return $attribute ? array_combine($attribute->master_data, $attribute->master_data) : [];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.parent.name')
                    ->getStateUsing(function (Product $record) {
                        return $record->category->parent->name ?? $record->category->name;
                    })
                    ->toggleable()
                    ->label('Category')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Sub Category')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('cost_price')
                    ->money('INR')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('INR')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stock')
                    ->searchable()
                    ->visible(function () {
                        $setting = Setting::where('code', 'PRODUCT_PROPERTY')->first();
                        return $setting ? !filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) : true;
                    }),
                Tables\Columns\TextColumn::make('variants_stock')
                    ->label('Stock')
                    ->getStateUsing(function (Product $record) {
                        return $record->variants->sum('stock');
                    })
                    ->visible(function () {
                        $setting = Setting::where('code', 'PRODUCT_PROPERTY')->first();
                        return $setting ? filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) : false;
                    }),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->options(function () {
                        $categories = Category::all()->groupBy('parent_id');
                        $options = [];

                        // First process all root categories (parent_id = null)
                        foreach ($categories[null] ?? [] as $parentCategory) {
                            $options[$parentCategory->id] = $parentCategory->name;

                            // Process children recursively with increasing depth
                            self::addChildrenWithDepth($categories, $options, $parentCategory->id);
                        }

                        return $options;
                    })
                    ->columnSpan(2)
                    ->preload()
                    ->searchable(),
            ], layout: FiltersLayout::AboveContent)
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
