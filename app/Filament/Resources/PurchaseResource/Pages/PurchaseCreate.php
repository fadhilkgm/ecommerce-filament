<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Models\Category;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Filament\Resources\PurchaseResource;
use App\Models\ProductAttribute;
use Filament\Resources\Pages\Page;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttribute;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\SupplierTransaction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions\Action as ActionsAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PurchaseCreate extends Page implements HasTable
{
    use InteractsWithTable;
    protected static string $resource = PurchaseResource::class;

    protected static string $view = 'filament.resources.purchase-resource.pages.purchase-create';
    protected static bool $shouldRegisterNavigation = false;

    public $category_id;
    public $purchase_number;
    public $product_id;
    public $purchase_id;
    public $quantity = 1;
    public $date;
    public $total_price;
    public $total_amount;
    public $price;
    public $product_options = [];
    public $variant_id;
    public $total_discount;
    public $discountedAmount;
    public $payment_method = 'cash';
    public $supplier_id;
    public $product_variant_choice;
    public $paid_amount;

    public function mount(): void
    {
        $this->product_options = Product::pluck('name', 'id');
        $this->purchase_number = self::generateUniqueOrderNumber();
        $this->date = now()->format('Y-m-d');
        $this->setInvoiceId();
    }

    public static function generateUniqueOrderNumber()
    {
        $lastOrder = Purchase::latest()->first();
        $orderNumber = $lastOrder ? ((int) substr($lastOrder->purchase_number, 4)) + 1 : 1;
        return 'PRC-' . str_pad($orderNumber, 6, '0', STR_PAD_LEFT);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Invoice Details')
                ->schema([
                    TextInput::make('purchase_number')
                        ->default($this->purchase_number)
                        ->dehydrated()
                        ->placeholder('Enter Invoice Number'),
                    DatePicker::make('date')
                        ->required()
                        ->default($this->date)
                        ->placeholder('Select Date'),
                    TextInput::make('total_amount')
                        ->numeric()
                        ->disabled()
                        ->placeholder('Total Amount'),
                    Select::make('supplier_id')->options(fn() => Supplier::all()->pluck('name', 'id'))->searchable()->preload()->label('Supplier')->required(),
                ])
                ->columns(3),

            Section::make('Product Details')
                ->schema([
                    Select::make('category_id')
                        ->label('Category')
                        ->searchable()
                        ->preload()
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
                        ->reactive()
                        ->required()
                        ->afterStateUpdated(function ($state, $set) {
                            $set('product_id', null);
                            $set('variant_id', null);
                            $set('price', null);
                            $set('total_price', null);
                        })
                        ->hidden(true)
                        ->placeholder('Select a category'),



                    Select::make('product_variant_choice')
                        ->label('Product')
                        ->options(function () {
                            $options = [];

                            Product::with('variants.attributes.attribute')
                                ->get()
                                ->each(function ($product) use (&$options) {
                                    if ($product->variants->isEmpty()) {
                                        $options["product-{$product->id}"] = $product->name;
                                    } else {
                                        $product->variants->each(function ($variant) use ($product, &$options) {
                                            $attributes = $variant->attributes->map(function ($attr) {
                                                return $attr->attribute->name . ': ' . $attr->value;
                                            })->implode(', ');
                                            $options["variant-{$variant->id}"] = "{$product->name} - {$attributes}";
                                        });
                                    }
                                });

                            return $options;
                        })
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->suffixAction(self::createProductAction())
                        ->required()
                        ->afterStateUpdated(function ($state, $set) {
                            if (Str::startsWith($state, 'variant-')) {
                                $variantId = (int) Str::after($state, 'variant-');
                                $variant = \App\Models\ProductVariant::with('product')->find($variantId);
                                $set('variant_id', $variant->id);
                                $set('product_id', $variant->product->id);
                                $set('price', $variant->cost_price ?? $variant->product->cost_price ?? 0);
                                $set('total_price', $variant->cost_price ?? $variant->product->cost_price ?? 0);
                            } elseif (Str::startsWith($state, 'product-')) {
                                $productId = (int) Str::after($state, 'product-');
                                $product = Product::find($productId);
                                $set('variant_id', null);
                                $set('product_id', $product->id);
                                $set('price', $product->cost_price ?? 0);
                                $set('total_price', $product->cost_price ?? 0);
                            } else {
                                $set('product_id', null);
                                $set('variant_id', null);
                                $set('price', null);
                                $set('total_price', null);
                            }
                        })
                        ->placeholder('Select a product')
                        ->columnSpanFull(),
                    TextInput::make('quantity')
                        ->numeric()
                        ->reactive()
                        ->default(1)
                        ->placeholder('Quantity')
                        ->afterStateUpdated(function ($state, $set, $get) {
                            if ($get('price')) {
                                $set('total_price', $get('price') * $state);
                            }
                        }),

                    TextInput::make('total_price')
                        ->numeric()
                        ->reactive()
                        ->placeholder('Total Price'),
                ])
                ->columns(2),
        ];
    }


    public function createProductAction(): ActionsAction
    {
        return  ActionsAction::make('add-product')
            ->label('Add Product')
            ->icon('heroicon-o-plus-circle')
            ->form([
                Grid::make(1)->schema([
                    TextInput::make('name'),
                    Select::make('category_id')
                        ->label('Category')
                        ->searchable()
                        ->preload()
                        ->suffixAction(
                            ActionsAction::make('add-category')
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
                                        })->searchable()->preload(),
                                    TextInput::make('name')
                                        ->required()
                                        ->maxLength(255),
                                ])->action(function ($data) {
                                    $data['shop_id'] = Filament::getTenant()->id;
                                    Category::create($data);
                                })
                        )
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
                        }),
                    TextInput::make('price'),
                    TextInput::make('cost_price'),


                    ...($this->shouldShowAttributeFields() ? $this->getDynamicAttributeFields() : [])

                ])->columns(2)
            ])->action(function ($data) {
                self::createProduct($data);
            });
    }


    //new

    protected function getDynamicAttributeFields(): array
    {
        return \App\Models\ProductAttribute::all()
            ->map(function ($attribute) {
                return Select::make("attributes.{$attribute->id}")
                    ->label($attribute->name)
                    ->options(array_combine($attribute->master_data, $attribute->master_data))
                    ->multiple()
                    ->searchable()
                    ->preload();
            })
            ->toArray();
    }



    private static function makeValuesOption($attributeId)
    {
        $attribute = ProductAttribute::find($attributeId);
        return $attribute ? array_combine($attribute->master_data, $attribute->master_data) : [];
    }

    public static function createProduct($data)
    {
        DB::beginTransaction();
        try {
            $product = Product::create([
                'name' => $data['name'],
                'category_id' => $data['category_id'],
                'cost_price' => $data['cost_price'],
                'price' => $data['price'],
                'shop_id' => Filament::getTenant()->id,
            ]);

            $attributes = $data['attributes'] ?? [];

            // ✅ Sanitize: remove invalid attribute keys (e.g., "0")
            $attributes = collect($attributes)
                ->filter(function ($values, $key) {
                    return (int)$key > 0 && is_array($values) && count($values) > 0;
                })
                ->mapWithKeys(function ($values, $key) {
                    return [(int)$key => $values]; // Cast key to int
                })
                ->toArray();

            // If no attributes, just create one base variant
            if (empty($attributes)) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $data['name'] . '-' . random_int(1000, 9999),
                    'stock' => 0,
                    'shop_id' => Filament::getTenant()->id,
                ]);
            } else {
                $attributeCombinations = self::generateCombinations($attributes);

                foreach ($attributeCombinations as $combination) {
                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $data['name'] . '-' . random_int(1000, 9999),
                        'stock' => 0,
                        'shop_id' => Filament::getTenant()->id,
                    ]);

                    foreach ($combination as $attributeId => $value) {
                        ProductVariantAttribute::create([
                            'product_variant_id' => $variant->id,
                            'product_attribute_id' => (int)$attributeId,
                            'value' => $value,
                        ]);
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return Notification::make()
            ->title('Product Created')
            ->success()
            ->send();
    }



    protected static function generateCombinations(array $attributes): array
    {
        $combinations = [[]];

        foreach ($attributes as $attributeId => $values) {
            $newCombinations = [];

            foreach ($combinations as $combination) {
                foreach ($values as $value) {
                    $newCombination = $combination;
                    $newCombination[$attributeId] = $value;
                    $newCombinations[] = $newCombination;
                }
            }

            $combinations = $newCombinations;
        }

        return $combinations;
    }





    protected function addChildrenWithDepth($groupedCategories, &$options, $parentId, $depth = 1)
    {
        foreach ($groupedCategories[$parentId] ?? [] as $childCategory) {
            $options[$childCategory->id] = str_repeat('-', $depth) . ' ' . $childCategory->name;

            // Recursively process grandchildren with increased depth
            $this->addChildrenWithDepth($groupedCategories, $options, $childCategory->id, $depth + 1);
        }
    }



    // TABLE

    public function table(Table $table): Table
    {
        return $table
            ->query(fn() => PurchaseItem::with('product', 'variant')
                ->where('purchase_id', $this->purchase_id))
            ->columns([
                TextColumn::make('category.name')
                    ->label('Category Name'),
                TextColumn::make('product.name')
                    ->label('Product Name'),

                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('INR'),

                TextColumn::make('quantity')
                    ->label('Quantity'),

                TextColumn::make('variant.sku')->getStateUsing(function (PurchaseItem $record) {
                    return implode('- ', $record->variant->attributes->pluck('value')->toArray());
                }),

                TextColumn::make('total')
                    ->label('Total Price')
                    ->money('INR')->summarize([
                        Sum::make('total')->label('Total Price')
                            ->money('INR'),
                    ]),
            ])->actions([
                Action::make('remove')->icon('heroicon-o-minus-circle')->size('xl')->action(function (PurchaseItem $record) {
                    $this->updateQuantity($record->id, -1);
                })->label(''),
                Action::make('add')->icon('heroicon-o-plus-circle')->size('xl')->action(function (PurchaseItem $record) {
                    $this->updateQuantity($record->id, 1);
                })->label(''),
            ])
            ->paginated(false);
    }




    // ADD ITEM ACTION

    public function submit()
    {
        $this->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
        ]);

        DB::beginTransaction();

        try {


            if (!$this->purchase_id) {
                $invoice = Purchase::create([
                    'shop_id' => Filament::getTenant()->id,
                    'purchase_number' => $this->purchase_number,
                    'supplier_id' => $this->supplier_id,
                    'status' => false,
                    'date' => $this->date,
                    'total_amount' => $this->total_amount,
                ]);
                $this->purchase_id = $invoice->id;
            }

            $product = Product::findOrFail($this->product_id);
            $this->category_id = $product->category_id;

            // Handle variant logic based on product property setting
            if (!$this->variant_id) {
                // Try to find existing variant or create one if none exists
                $variant = ProductVariant::where('product_id', $this->product_id)->first();
                
                if (!$variant) {
                    // Create a default variant for this product
                    $variant = ProductVariant::create([
                        'product_id' => $this->product_id,
                        'sku' => $product->name . '-' . random_int(1000, 9999),
                        'stock' => 0,
                        'shop_id' => Filament::getTenant()->id,
                    ]);
                }
                
                $this->variant_id = $variant->id;
            }

            $existingItem = PurchaseItem::where('purchase_id', $this->purchase_id)
                ->where('product_id', $this->product_id)
                ->where('product_variant_id', $this->variant_id)
                ->first();

            if ($existingItem) {
                $existingItem->update([
                    'unit_price' => $this->price,
                    'quantity' => $existingItem->quantity + $this->quantity,
                    'total' => $this->total_price,
                ]);
            } else {
                PurchaseItem::create([
                    'purchase_id' => $this->purchase_id,
                    'category_id' => $this->category_id,
                    'product_id' => $this->product_id,
                    'product_variant_id' => $this->variant_id,
                    'quantity' => $this->quantity,
                    'unit_price' => $this->price,
                    'total' => $this->total_price,
                ]);
            }

            $this->updateTotalAmount();
            DB::commit();

            $this->reset([
                'product_variant_choice',
                'product_id',
                'category_id',
                'price',
                'total_price',
                'quantity',
                'variant_id',
            ]);

            Notification::make()->title('Item Added Successfully')->success()->send();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in submit function: ' . $e->getMessage());
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        }
    }


    public function updateQuantity($id, int $change, $table = false): void
    {
        $purchaseItem = PurchaseItem::find($id);
        if (!$purchaseItem) {
            Notification::make()->title('Item not found')->danger()->send();
            return;
        }

        if ($table) {
            $newQuantity = $change;
        } else {
            $newQuantity = max(0, $purchaseItem->quantity + $change);
        }

        if ($newQuantity == 0) {
            $purchaseItem->delete();
            Notification::make()->title('Item Removed')->success()->send();
        } else {
            $purchaseItem->update([
                'quantity' => $newQuantity,
                'total' => $purchaseItem->unit_price * $newQuantity,
            ]);
            Notification::make()->title('Quantity Updated')->success()->send();
        }

        // Update the total amount
        $this->updateTotalAmount();
    }

    public function updateTotalDiscount()
    {
        // First get the raw subtotal without any discount
        $subtotal = PurchaseItem::where('purchase_id', $this->purchase_id)->sum('total');


        $this->total_amount = $subtotal;


        // Format the total amount with 2 decimal places
        $this->total_amount = number_format($this->total_amount, 2, '.', '');
        $this->discountedAmount = number_format($this->discountedAmount, 2, '.', '');
    }

    private function updateTotalAmount(): void
    {
        // Calculate the sum of all items' totals
        $subtotal = PurchaseItem::where('purchase_id', $this->purchase_id)->sum('total');
        $this->total_amount = $subtotal;
    }

    public function createOrder()
    {
        if (!$this->purchase_id) {
            return;
        }

        DB::beginTransaction();
        try {
            $purchase = Purchase::findOrFail($this->purchase_id);
            if ($purchase->status) {
                Notification::make()->title('Invalid purchase')->warning()->send();
                return;
            }

            $purchase->update([
                'date' => $this->date,
                'status' => true,
                'supplier_id' => $this->supplier_id,
                'total_amount' => $this->total_amount,
            ]);

            $paidAmount = $this->paid_amount ?? 0;
            $totalAmount = $this->total_amount;
            $paymentMethod = $this->payment_method;
            $balance = $totalAmount - $paidAmount;

            if ($paidAmount > 0 && $paidAmount < $totalAmount && $paymentMethod === 'cash') {
                // Partial cash payment
                SupplierTransaction::create([
                    'supplier_id' => $this->supplier_id,
                    'purchase_id' => $this->purchase_id,
                    'transaction_number' => $this->purchase_number,
                    'amount' => $paidAmount,
                    'date' => $this->date,
                    'type' => 'debit',
                    'user_id' => auth()->user()->id,
                    'shop_id' => Filament::getTenant()->id,
                ]);

                SupplierTransaction::create([
                    'supplier_id' => $this->supplier_id,
                    'purchase_id' => $this->purchase_id,
                    'transaction_number' => $this->purchase_number,
                    'amount' => $balance,
                    'date' => $this->date,
                    'type' => 'credit',
                    'user_id' => auth()->user()->id,
                    'shop_id' => Filament::getTenant()->id,
                ]);
            } else {
                // Full cash payment (paidAmount >= totalAmount) => full debit
                // Or no payment yet => full credit
                $type = ($paymentMethod === 'cash' && $paidAmount >= $totalAmount) ? 'debit' : 'credit';
                $amount = ($type === 'debit') ? $paidAmount : $totalAmount;

                SupplierTransaction::create([
                    'supplier_id' => $this->supplier_id,
                    'purchase_id' => $this->purchase_id,
                    'transaction_number' => $this->purchase_number,
                    'amount' => $amount,
                    'date' => $this->date,
                    'type' => $type,
                    'user_id' => auth()->user()->id,
                    'shop_id' => Filament::getTenant()->id,
                ]);
            }

            $items = PurchaseItem::where('purchase_id', $this->purchase_id)->get();
            foreach ($items as $item) {
                $productVariant = ProductVariant::find($item->product_variant_id);
                $productVariant->update([
                    'stock' => $productVariant->stock + $item->quantity
                ]);
            }

            DB::commit();
            Notification::make()->title('Purchase Order Created')->success()->send();
            return redirect(Filament::getTenant()->slug . '/purchases');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in createOrder: ' . $e->getMessage());
            Notification::make()->title('Error Creating Order')->danger()->send();
        }
    }

    public function createOrderAndPrint()
    {
        $this->createOrder();
        $invoice = Purchase::find($this->purchase_id);

        if ($invoice) {
            $downloadUrl = route('download.invoice', ['invoice' => $invoice]);
            $this->dispatch('downloadInvoice', $downloadUrl);
            sleep(1.5);
            return redirect(Filament::getTenant()->slug . '/purchases');
        }

        Notification::make()->title('Failed to Create Order')->danger()->send();
    }

    private function setInvoiceId(): void
    {
        $purchase = Purchase::where('status', false)->latest()->first();
        if ($purchase) {
            $this->purchase_id = $purchase->id;
            $this->total_amount = PurchaseItem::where('purchase_id', $this->purchase_id)->sum('total');
            $this->purchase_number = $purchase->purchase_number;
            $this->supplier_id = $purchase->supplier_id;
        } else {
            $this->purchase_id = null;
            $this->purchase_number = self::generateUniqueOrderNumber();
        }
    }


    // CLEAR CART ACTION
    public function clearCart(): void
    {
        // // Fetch all invoice items for the current invoice
        // $invoiceItems = PurchaseItem::where('purchase_id', $this->purchase_id)->get();

        // // Loop through each item and restore the stock
        // foreach ($invoiceItems as $invoiceItem) {
        //     $stockEntry = ProductVariant::find($invoiceItem->product_variant_id);

        //     if ($stockEntry) {
        //         // Restore the stock by adding the quantity of the deleted item
        //         $stockEntry->update([
        //             'stock' => $stockEntry->stock + $invoiceItem->quantity,
        //         ]);
        //     }
        // }

        // // Delete all invoice items for the current invoice
        PurchaseItem::where('purchase_id', $this->purchase_id)->delete();

        // Reset the table or any other necessary cleanup
        $this->resetTable();
        $this->updateTotalAmount();

        // Notify the user
        Notification::make()->title('Cart Cleared')->success()->send();
    }

    protected function shouldShowAttributeFields(): bool
    {
        $setting = \App\Models\Setting::where('code', 'PRODUCT_PROPERTY')->first();
        return $setting ? filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) : false;
    }
}
