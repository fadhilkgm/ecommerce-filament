<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Models\Category;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Filament\Resources\PurchaseResource;
use Filament\Resources\Pages\Page;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttribute;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
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

class PurchaseEdit extends Page implements HasTable
{
    use InteractsWithTable;
    protected static string $resource = PurchaseResource::class;

    protected static string $view = 'filament.resources.purchase-resource.pages.purchase-edit';
    protected static bool $shouldRegisterNavigation = false;

    public $category_id;
    public $purchase_number;
    public $customer_name;
    public $customer_phone;
    public $product_id;
    public $purchase_id;
    public $quantity = 1;
    public $date;
    public $total_price;
    public $total_amount;
    public $price;
    public $product_options = [];
    public $attribute_1;
    public $attribute_2;
    public $attribute_3;
    public $variant_id;
    public $total_discount;
    public $discountedAmount;
    public $payment_method;
    public $supplier_id;
    public $record;

    public function mount(int|string $record): void
    {
        // Resolve the record using Filament's method
        $this->record = $this->resolveRecord($record);

        $this->purchase_id = $this->record->id;
        $this->supplier_id = $this->record->supplier_id;
        $this->payment_method = $this->record->payment_method;
        $this->total_amount = $this->record->total_amount;
        $this->product_options = Product::pluck('name', 'id');
        $this->purchase_number = $this->record->purchase_number;
        $this->date = now()->format('Y-m-d');
    }

    protected function resolveRecord(int|string $key): Purchase
    {
        return static::getResource()::resolveRecordRouteBinding($key);
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
                    Select::make('supplier_id')->options(fn() => Supplier::all()->pluck('name', 'id'))->searchable()->preload(),
                    Select::make('payment_method')
                        ->options([
                            'cash' => 'Cash',
                            'credit' => 'Credit',
                        ])
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
                            $set('attribute_1', null);
                            $set('attribute_2', null);
                            $set('attribute_3', null);
                            $set('variant_id', null);
                            $set('price', null);
                            $set('total_price', null);
                        })
                        ->hidden(true)
                        ->placeholder('Select a category'),

                    Select::make('product_id')
                        ->label('Product')
                        ->options(function (callable $get) {
                            if (!$get('category_id')) {
                                return Product::all()->pluck('name', 'id');
                            }
                            return Product::where('category_id', $get('category_id'))->pluck('name', 'id');
                        })
                        ->preload()
                        ->searchable()
                        ->reactive()
                        ->required()
                        ->placeholder('Select a product')
                        ->afterStateUpdated(function ($state, $set) {
                            $set('attribute_1', null);
                            $set('attribute_2', null);
                            $set('attribute_3', null);
                            $set('variant_id', null);
                            $set('price', null);
                            $set('total_price', null);
                        })->columnSpanFull(),

                    // Dynamic attribute fields
                    Select::make('attribute_1')
                        ->label(fn(callable $get) => $this->getAttributeLabel($get('product_id'), 1) ?? 'Attribute 1')
                        ->options(fn(callable $get) => $this->getAvailableAttributes($get('product_id'), 1))
                        ->reactive()
                        ->afterStateUpdated(fn($state, $set, $get) => $this->updateVariantDetails($get, $set))
                        ->hidden(fn(callable $get) => !$this->hasAttribute($get('product_id'), 1)),

                    Select::make('attribute_2')
                        ->label(fn(callable $get) => $this->getAttributeLabel($get('product_id'), 2) ?? 'Attribute 2')
                        ->options(fn(callable $get) => $this->getAvailableAttributes($get('product_id'), 2, $get('attribute_1')))
                        ->reactive()
                        ->afterStateUpdated(fn($state, $set, $get) => $this->updateVariantDetails($get, $set))
                        ->hidden(fn(callable $get) => !$this->hasAttribute($get('product_id'), 2)),

                    Select::make('attribute_3')
                        ->label(fn(callable $get) => $this->getAttributeLabel($get('product_id'), 3) ?? 'Attribute 3')
                        ->options(fn(callable $get) => $this->getAvailableAttributes($get('product_id'), 3, $get('attribute_1'), $get('attribute_2')))
                        ->reactive()
                        ->afterStateUpdated(fn($state, $set, $get) => $this->updateVariantDetails($get, $set))
                        ->hidden(fn(callable $get) => !$this->hasAttribute($get('product_id'), 3)),

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

    protected function addChildrenWithDepth($groupedCategories, &$options, $parentId, $depth = 1)
    {
        foreach ($groupedCategories[$parentId] ?? [] as $childCategory) {
            $options[$childCategory->id] = str_repeat('-', $depth) . ' ' . $childCategory->name;

            // Recursively process grandchildren with increased depth
            $this->addChildrenWithDepth($groupedCategories, $options, $childCategory->id, $depth + 1);
        }
    }

    // Helper methods
    protected function hasAttribute($productId, $attributeNumber): bool
    {
        if (!$productId) {
            return false;
        }

        $attributeName = $this->getAttributeLabel($productId, $attributeNumber);
        return !empty($attributeName);
    }

    protected function getAttributeLabel($productId, $attributeNumber): ?string
    {
        if (!$productId) {
            return null;
        }

        $attributes = ProductVariantAttribute::whereHas('variant', function ($query) use ($productId) {
            $query->where('product_id', $productId);
        })
            ->with('attribute')
            ->get()
            ->pluck('attribute.name')
            ->unique()
            ->values()
            ->toArray();

        return $attributes[$attributeNumber - 1] ?? null;
    }

    protected function getAvailableAttributes($productId, $attributeNumber, ...$selectedAttributes): Collection
    {
        if (!$productId) {
            return collect();
        }

        $attributeName = $this->getAttributeLabel($productId, $attributeNumber);
        if (!$attributeName) {
            return collect();
        }

        $query = ProductVariantAttribute::whereHas('attribute', function ($query) use ($attributeName) {
            $query->where('name', $attributeName);
        })
            ->whereHas('variant', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            });

        // Apply filters based on previously selected attributes
        for ($i = 1; $i < $attributeNumber; $i++) {
            if (!empty($selectedAttributes[$i - 1])) {
                $prevAttributeName = $this->getAttributeLabel($productId, $i);
                $query->whereHas('variant.attributes', function ($q) use ($prevAttributeName, $selectedAttributes, $i) {
                    $q->whereHas('attribute', function ($q) use ($prevAttributeName) {
                        $q->where('name', $prevAttributeName);
                    })
                        ->where('value', $selectedAttributes[$i - 1]);
                });
            }
        }

        return $query->pluck('value', 'value')->unique();
    }

    protected function updateVariantDetails(callable $get, callable $set): void
    {
        $productId = $get('product_id');
        $attribute1 = $get('attribute_1');
        $attribute2 = $get('attribute_2');
        $attribute3 = $get('attribute_3');

        if (!$productId) {
            return;
        }

        // Get the base product price
        $product = Product::find($productId);
        if ($product) {
            $set('price', $product->price);
        }

        // Determine how many attributes exist for this product
        $attributes = ProductVariantAttribute::whereHas('variant', function ($query) use ($productId) {
            $query->where('product_id', $productId);
        })
            ->with('attribute')
            ->get()
            ->pluck('attribute.name')
            ->unique()
            ->values()
            ->toArray();

        $attributeCount = count($attributes);

        // Build variant query
        $variantQuery = ProductVariant::where('product_id', $productId);

        if ($attributeCount >= 1 && $attribute1) {
            $attributeName1 = $attributes[0] ?? null;
            $variantQuery->whereHas('attributes', function ($query) use ($attributeName1, $attribute1) {
                $query->whereHas('attribute', function ($subQuery) use ($attributeName1) {
                    $subQuery->where('name', $attributeName1);
                })->where('value', $attribute1);
            });
        }

        if ($attributeCount >= 2 && $attribute2) {
            $attributeName2 = $attributes[1] ?? null;
            $variantQuery->whereHas('attributes', function ($query) use ($attributeName2, $attribute2) {
                $query->whereHas('attribute', function ($subQuery) use ($attributeName2) {
                    $subQuery->where('name', $attributeName2);
                })->where('value', $attribute2);
            });
        }

        if ($attributeCount == 3 && $attribute3) {
            $attributeName3 = $attributes[2] ?? null;
            $variantQuery->whereHas('attributes', function ($query) use ($attributeName3, $attribute3) {
                $query->whereHas('attribute', function ($subQuery) use ($attributeName3) {
                    $subQuery->where('name', $attributeName3);
                })->where('value', $attribute3);
            });
        }

        $variant = $variantQuery->first();

        if ($variant) {
            $set('variant_id', $variant->id);
            $set('price', $variant->price ?? $product->price);
        }

        // Ensure total price updates correctly
        $set('total_price', $get('price') * $get('quantity'));
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

                TextInputColumn::make('quantity')
                    ->label('Quantity')->afterStateUpdated(function (PurchaseItem $record, $state) {
                        $this->updateQuantity($record->id, $state,true);
                    }),

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
        // Validate input fields
        $this->validate([
            'product_id' => 'required|exists:products,id', // Ensure product exists
            'variant_id' => 'nullable|exists:product_variants,id', // Ensure variant exists if provided
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'date' => 'required|date',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        // Use a database transaction to ensure atomicity
        DB::beginTransaction();

        try {
            // Set or create the invoice
            $this->setInvoiceId();

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

            // Fetch the product
            $product = Product::findOrFail($this->product_id);
            $this->category_id = $product->category_id;

            // Check if the item already exists in the invoice
            $existingItem = PurchaseItem::where('purchase_id', $this->purchase_id)
                ->where('product_id', $this->product_id)
                ->where('product_variant_id', $this->variant_id)
                ->first();

            if ($existingItem) {
                // Calculate the new quantity
                $newQuantity = $existingItem->quantity + $this->quantity;

                // Update the existing item
                $existingItem->update([
                    'unit_price' => $this->price,
                    'quantity' => $newQuantity,
                    'total' => $this->total_price,
                ]);
            } else {
                // Create a new purchase item
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

            // Update the total amount of the purchase
            $this->updateTotalAmount();

            // Commit the transaction
            DB::commit();

            // Reset input fields
            $this->reset([
                'product_id',
                'category_id',
                'price',
                'total_price',
                'quantity',
                'variant_id',
                'attribute_1',
                'attribute_2',
            ]);

            // Notify the user of success
            Notification::make()->title('Item Added Successfully')->success()->send();
        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();

            // Log the error for debugging
            Log::error('Error in submit function: ' . $e->getMessage());

            // Notify the user of the error
            Notification::make()->title('An error occurred. Please try again.')->danger()->send();
        }
    }

    public function updateQuantity($id, int $change, $table = false): void
    {
        $purchaseItem = PurchaseItem::find($id);
        if (!$purchaseItem) {
            Notification::make()->title('Item not found')->danger()->send();
            return;
        }

        if($table){
            $newQuantity = $change;
        }else{
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

            $purchase->update([
                'date' => $this->date,
                'status' => true,
                'supplier_id' => $this->supplier_id,
                'total_amount' => $this->total_amount,
            ]);

            DB::commit();
            Notification::make()->title('Purchase Order updated')->success()->send();
            return redirect(Filament::getTenant()->slug . '/purchases');
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title('Error Updating Purchase Order')->danger()->send();
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
            return redirect('invoices');
        }

        Notification::make()->title('Failed to Create Order')->danger()->send();
    }

    private function setInvoiceId(): void
    {
        $invoice = Purchase::where('status', false)->latest()->first();
        if ($invoice) {
            $this->purchase_id = $invoice->id;
            $this->total_amount = PurchaseItem::where('purchase_id', $this->purchase_id)->sum('total');
            $this->purchase_number = $invoice->purchase_number;
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
}
