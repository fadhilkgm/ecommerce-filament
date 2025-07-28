<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttribute;
use App\Models\Transaction;
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
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CreateInvoice extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = InvoiceResource::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.resources.invoice-resource.pages.create-invoice';

    public $category_id;

    public $invoice_number;

    public $customer_name;

    public $customer_phone;

    public $product_id;

    public $invoice_id;

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

    public $product_variant_choice;

    // Livewire or Filament component

    public $payment_method = ['cash'];  // Now accepts multiple (array)

    public $paid_amount = null;  // Used for cash amount

    public $upi_amount = null;  // Used if UPI is selected

    public $card_amount = null;  // Used if card is selected

    public function mount(): void
    {
        $this->product_options = Product::pluck('name', 'id');
        $this->invoice_number = self::generateUniqueOrderNumber();
        $this->date = now()->format('Y-m-d');
    }

    public static function generateUniqueOrderNumber()
    {
        $lastOrder = Invoice::latest()->first();
        $orderNumber = $lastOrder ? ((int) substr($lastOrder->invoice_number, 4)) + 1 : 1;
        return 'INV-' . str_pad($orderNumber, 6, '0', STR_PAD_LEFT);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Invoice Details')
                ->schema([
                    TextInput::make('invoice_number')
                        ->default($this->invoice_number)
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
                    Select::make('customer_name')
                        ->label('Customer')
                        ->options(fn() => \App\Models\Customer::pluck('name', 'name'))
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(function ($state, $set) {
                            if ($state) {
                                $customer = \App\Models\Customer::where('name', $state)->first();
                                if ($customer) {
                                    $set('customer_phone', $customer->phone);
                                }
                            } else {
                                $set('customer_phone', null);
                            }
                        })
                        ->placeholder('Select Customer'),
                    TextInput::make('customer_phone')
                        ->numeric()
                        ->placeholder('Customer Phone'),
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
                        ->required()
                        ->afterStateUpdated(function ($state, $set) {
                            if (Str::startsWith($state, 'variant-')) {
                                $variantId = (int) Str::after($state, 'variant-');
                                $variant = \App\Models\ProductVariant::with('product')->find($variantId);
                                $set('variant_id', $variant->id);
                                $set('product_id', $variant->product->id);
                                $set('price', $variant->price ?? $variant->product->price ?? 0);
                                $set('total_price', $variant->price ?? $variant->product->price ?? 0);
                            } elseif (Str::startsWith($state, 'product-')) {
                                $productId = (int) Str::after($state, 'product-');
                                $product = Product::find($productId);
                                $set('variant_id', null);
                                $set('product_id', $product->id);
                                $set('price', $product->price ?? 0);
                                $set('total_price', $product->price ?? 0);
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

    private static function makeValuesOption($attributeId)
    {
        $attribute = ProductAttribute::find($attributeId);
        return $attribute ? array_combine($attribute->master_data, $attribute->master_data) : [];
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
            ->query(fn() => InvoiceItem::with('product', 'variant')
                ->where('invoice_id', $this->invoice_id))
            ->columns([
                TextColumn::make('category.name')
                    ->label('Category Name'),
                TextColumn::make('product.name')
                    ->label('Product Name'),
                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('INR'),
                TextInputColumn::make('quantity')
                    ->label('Quantity'),
                TextColumn::make('variant.sku')->getStateUsing(function (InvoiceItem $record) {
                    return implode('- ', $record->variant->attributes->pluck('value')->toArray());
                }),
                TextColumn::make('total')
                    ->label('Total Price')
                    ->money('INR')
                    ->summarize([
                        Sum::make('total')
                            ->label('Total Price')
                            ->money('INR'),
                    ]),
            ])
            ->actions([
                Action::make('remove')->icon('heroicon-o-minus-circle')->size('xl')->action(function (InvoiceItem $record) {
                    $this->updateQuantity($record->id, -1);
                })->label(''),
                Action::make('add')->icon('heroicon-o-plus-circle')->size('xl')->action(function (InvoiceItem $record) {
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
            'product_id' => 'required|exists:products,id',  // Ensure product exists
            'variant_id' => 'nullable|exists:product_variants,id',  // Ensure variant exists if provided
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'date' => 'required|date',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
        ]);

        // Use a database transaction to ensure atomicity
        DB::beginTransaction();

        try {
            // Set or create the invoice
            $this->setInvoiceId();

            if (!$this->invoice_id) {
                $invoice = Invoice::create([
                    'date' => $this->date,
                    'invoice_number' => $this->invoice_number,
                    'customer_name' => $this->customer_name,
                    'customer_phone' => $this->customer_phone,
                    'total_amount' => 0,
                    'user_id' => Auth::id(),
                    'status' => false,
                ]);
                $this->invoice_id = $invoice->id;
            }

            // Fetch the product
            $product = Product::findOrFail($this->product_id);
            $this->category_id = $product->category_id;

            // Fetch the product variant or create a default one if none exists
            $stockEntry = null;

            if ($this->variant_id) {
                // Specific variant selected
                $stockEntry = ProductVariant::where('product_id', $this->product_id)
                    ->where('id', $this->variant_id)
                    ->lockForUpdate()
                    ->first();
            } else {
                // No specific variant, try to find any variant for this product
                $stockEntry = ProductVariant::where('product_id', $this->product_id)
                    ->lockForUpdate()
                    ->first();
                
                // If no variant exists, create a default one
                if (!$stockEntry) {
                    $stockEntry = ProductVariant::create([
                        'product_id' => $this->product_id,
                        'sku' => $product->name . '-' . random_int(1000, 9999),
                        'stock' => 999, // Default stock
                        'shop_id' => Filament::getTenant()->id,
                    ]);
                    $this->variant_id = $stockEntry->id; // Set the variant_id for the invoice item
                }
            }

            if (!$stockEntry) {
                Notification::make()->title('Product Variant Not Found')->danger()->send();
                DB::rollBack();
                return;
            }

            // Check if there is sufficient stock
            if ($stockEntry->stock < $this->quantity) {
                Notification::make()->title('Insufficient Stock')->danger()->send();
                DB::rollBack();
                return;
            }

            // Check if the item already exists in the invoice
            $existingItem = InvoiceItem::where('invoice_id', $this->invoice_id)
                ->where('product_id', $this->product_id)
                ->where('product_variant_id', $this->variant_id)
                ->first();

            if ($existingItem) {
                // Calculate the new quantity
                $newQuantity = $existingItem->quantity + $this->quantity;

                // Check if the updated quantity exceeds available stock
                if ($newQuantity > $stockEntry->stock) {
                    $this->reset(['variant_id', 'attribute_1', 'attribute_2']);
                    Notification::make()->title('Insufficient Stock for Updated Quantity')->danger()->send();
                    DB::rollBack();
                    return;
                }

                // Update the existing item
                $existingItem->update([
                    'unit_price' => $this->price,
                    'quantity' => $newQuantity,
                    'total' => $this->total_price,
                ]);
            } else {
                // Create a new invoice item
                InvoiceItem::create([
                    'invoice_id' => $this->invoice_id,
                    'category_id' => $this->category_id,
                    'product_id' => $this->product_id,
                    'product_variant_id' => $this->variant_id,
                    'quantity' => $this->quantity,
                    'unit_price' => $this->price,
                    'total' => $this->total_price,
                ]);
            }

            // Decrement the stock
            $stockEntry->decrement('stock', $this->quantity);

            // Update the total amount of the invoice
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

    public function updateQuantity($id, int $change): void
    {
        $invoiceItem = InvoiceItem::find($id);
        if (!$invoiceItem) {
            Notification::make()->title('Item not found')->danger()->send();
            return;
        }

        $newQuantity = max(0, $invoiceItem->quantity + $change);
        $stockEntry = ProductVariant::find($invoiceItem->product_variant_id);

        if (!$stockEntry) {
            Notification::make()->title('Stock entry not found')->danger()->send();
            return;
        }

        // Calculate the current stock, considering the existing quantity in the invoice item
        $currentStock = $stockEntry->stock + $invoiceItem->quantity;

        if ($newQuantity == 0) {
            // Restore the full stock when deleting the invoice item
            $stockEntry->update(['stock' => $currentStock]);
            $invoiceItem->delete();
            Notification::make()->title('Item Removed')->success()->send();
        } else {
            // Check if the new quantity exceeds the available stock
            if ($newQuantity > $currentStock) {
                Notification::make()->title('Insufficient Stock')->danger()->send();
                return;
            }

            // Update the stock and invoice item
            $stockEntry->update(['stock' => $currentStock - $newQuantity]);

            $invoiceItem->update([
                'quantity' => $newQuantity,
                'total' => $invoiceItem->unit_price * $newQuantity,
            ]);
            Notification::make()->title('Quantity Updated')->success()->send();
        }

        // Update the total amount (this will handle discount if needed)
        $this->updateTotalAmount();
    }

    public function updateTotalDiscount()
    {
        // First get the raw subtotal without any discount
        $subtotal = InvoiceItem::where('invoice_id', $this->invoice_id)->sum('total');

        // Ensure total_discount is properly formatted
        $this->total_discount = trim($this->total_discount);

        if ($this->total_discount === '' || !is_numeric($this->total_discount)) {
            $this->total_discount = 0;
            $this->discountedAmount = 0;
            $this->total_amount = $subtotal;
        } else {
            $discountPercentage = (float) $this->total_discount;
            $this->discountedAmount = ($subtotal * $discountPercentage) / 100;
            $this->total_amount = $subtotal - $this->discountedAmount;
        }

        // Format the total amount with 2 decimal places
        $this->total_amount = number_format($this->total_amount, 2, '.', '');
        $this->discountedAmount = number_format($this->discountedAmount, 2, '.', '');
    }

    private function updateTotalAmount(): void
    {
        // Calculate the sum of all items' totals
        $subtotal = InvoiceItem::where('invoice_id', $this->invoice_id)->sum('total');

        // If there's no discount or it's 0, just use the subtotal
        if (!$this->total_discount || $this->total_discount == 0) {
            $this->total_amount = $subtotal;
            $this->discountedAmount = 0;
        } else {
            // Otherwise apply the discount
            $this->updateTotalDiscount();
        }
    }

    public function createOrder()
    {
        if (!$this->invoice_id) return;
    
        DB::beginTransaction();
    
        try {
            $invoice = Invoice::findOrFail($this->invoice_id);
    
            if ($invoice->status) {
                Notification::make()->title('Invalid Invoice')->warning()->send();
                return;
            }
    
            // Update invoice details
            $invoice->update([
                'date' => $this->date,
                'status' => true,
                'customer_name' => $this->customer_name,
                'customer_phone' => $this->customer_phone,
                'total_discount' => $this->total_discount,
                'total_amount' => $this->total_amount,
                'payment_method' => $this->payment_method,
            ]);
    
            $transactionNumber =  $this->invoice_number;
            $methods = $this->payment_method;
            $shopId = Filament::getTenant()->id;
            $userId = auth()->id();
    
            $cashAmount = $upiAmount = $cardAmount = 0;
    
            if (count($methods) === 2) {
                if (in_array('cash', $methods) && in_array('upi', $methods)) {
                    $cashAmount = floatval($this->paid_amount);
                    $upiAmount = $this->total_amount - $cashAmount;
                } elseif (in_array('cash', $methods) && in_array('card', $methods)) {
                    $cashAmount = floatval($this->paid_amount);
                    $cardAmount = $this->total_amount - $cashAmount;
                } elseif (in_array('upi', $methods) && in_array('card', $methods)) {
                    $upiAmount = floatval($this->upi_amount);
                    $cardAmount = $this->total_amount - $upiAmount;
                }
            } elseif (count($methods) === 1) {
                switch ($methods[0]) {
                    case 'cash':
                        $cashAmount = $this->total_amount;
                        break;
                    case 'upi':
                        $upiAmount = $this->total_amount;
                        break;
                    case 'card':
                        $cardAmount = $this->total_amount;
                        break;
                }
            }
    
            $totalCredit = $cashAmount + $upiAmount + $cardAmount;
    
            // Check mismatch before proceeding
            if ($totalCredit != $this->total_amount) {
                $this->addError('payment_mismatch', 'Total of payment amounts does not match the invoice total.');
                return;
            }
    
            // 1. DEBIT ENTRY (sales amount)
            Transaction::create([
                'amount' => $this->total_amount,
                'date' => $this->date,
                'transaction_number' => $transactionNumber,
                'type' => 'debit',
                'transaction_type' => 'sales',
                'shop_id' => $shopId,
                'user_id' => $userId,
                'payment_method' => implode(',', $methods),
            ]);
          
    
            // 2. CREDIT ENTRIES (per method)
            foreach ([
                'cash' => $cashAmount,
                'upi' => $upiAmount,
                'card' => $cardAmount,
            ] as $method => $amount) {
                if ($amount > 0) {
                    sleep(1);
                    Transaction::create([
                        'amount' => $amount,
                        'date' => $this->date,
                        'transaction_number' => $transactionNumber,
                        'type' => 'credit',
                        'transaction_type' => 'sales',
                        'shop_id' => $shopId,
                        'user_id' => $userId,
                        'payment_method' => $method,
                    ]);
                }
            }
    
            DB::commit();
    
            Notification::make()->title('Order Created')->success()->send();
            return redirect(Filament::getTenant()->slug . '/invoices');
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            Notification::make()->title('Error Creating Order')->danger()->send();
        }
    }
    

    public function createOrderAndPrint()
    {
        $this->createOrder();
        $invoice = Invoice::find($this->invoice_id);

        if ($invoice) {
            $downloadUrl = route('download.invoice', ['invoice' => $invoice]);
            $this->dispatch('downloadInvoice', $downloadUrl);
            sleep(1.5);
            return $this->getResource()::getUrl('index');
        }

        Notification::make()->title('Failed to Create Order')->danger()->send();
    }

    private function setInvoiceId(): void
    {
        $invoice = Invoice::where('status', false)->latest()->first();
        if ($invoice) {
            $this->invoice_id = $invoice->id;
            $this->total_amount = InvoiceItem::where('invoice_id', $this->invoice_id)->sum('total');
            $this->invoice_number = $invoice->invoice_number;
        } else {
            $this->invoice_id = null;
            $this->invoice_number = self::generateUniqueOrderNumber();
        }
    }

    // CLEAR CART ACTION
    public function clearCart(): void
    {
        // Fetch all invoice items for the current invoice
        $invoiceItems = InvoiceItem::where('invoice_id', $this->invoice_id)->get();

        // Loop through each item and restore the stock
        foreach ($invoiceItems as $invoiceItem) {
            $stockEntry = ProductVariant::find($invoiceItem->product_variant_id);

            if ($stockEntry) {
                // Restore the stock by adding the quantity of the deleted item
                $stockEntry->update([
                    'stock' => $stockEntry->stock + $invoiceItem->quantity,
                ]);
            }
        }

        // Delete all invoice items for the current invoice
        InvoiceItem::where('invoice_id', $this->invoice_id)->delete();

        // Reset the table or any other necessary cleanup
        $this->resetTable();
        $this->updateTotalAmount();

        // Notify the user
        Notification::make()->title('Cart Cleared')->success()->send();
    }

    public function togglePaymentMethod($type)
    {
        if (in_array($type, $this->payment_method)) {
            $this->payment_method = array_values(array_diff($this->payment_method, [$type]));
        } else {
            if (count($this->payment_method) < 2) {
                $this->payment_method[] = $type;
            }
        }
    }
}
