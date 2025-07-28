<x-filament::page>
    <!-- Add Item Form -->
    <form wire:submit.prevent="submit">
        {{ $this->form }}

        <x-filament::button type="submit" id="add-item" class="mt-2" color="success">
            Add Item
        </x-filament::button>
    </form>

    <!-- Cart Table -->
    @if ($this->invoice_id && \App\Models\InvoiceItem::where('invoice_id', $this->invoice_id)->exists())
    <div class="mt-6">
        {{ $this->table }}
    </div>

    <!-- Payment Method and Buttons -->
    <div class="space-y-6">
        <!-- Payment Method Selection -->
        <x-filament::section class="bg-white shadow-sm border border-gray-200 rounded-lg">
            <x-slot name="heading">
                <h3 class="text-lg font-semibold text-gray-800">
                    Select Payment Type
                </h3>
            </x-slot>

            <div class="grid grid-flow-col auto-cols-fr gap-2">
                <x-filament::button icon="heroicon-o-banknotes" wire:click="togglePaymentMethod('cash')"
                    color="{{ in_array('cash', $payment_method) ? 'primary' : 'info' }}"
                    :outlined="!in_array('cash', $payment_method)"
                    class="py-2 px-4 rounded-md transition-all duration-300">
                    Cash
                </x-filament::button>

                {{-- Payment Type: Card --}}
                <x-filament::button icon="heroicon-o-credit-card" wire:click="togglePaymentMethod('card')"
                    color="{{ in_array('card', $payment_method) ? 'primary' : 'info' }}"
                    :outlined="!in_array('card', $payment_method)"
                    class="py-2 px-4 rounded-md transition-all duration-300">
                    Card
                </x-filament::button>

                {{-- Payment Type: UPI --}}
                <x-filament::button icon="heroicon-o-qr-code" wire:click="togglePaymentMethod('upi')"
                    color="{{ in_array('upi', $payment_method) ? 'primary' : 'info' }}"
                    :outlined="!in_array('upi', $payment_method)"
                    class="py-2 px-4 rounded-md transition-all duration-300">
                    UPI
                </x-filament::button>

            </div>
        </x-filament::section>

        @php
        $showCashInput = false;
        $showUpiInput = false;

        $selected = $payment_method;

        if (count($selected) === 2) {
        if (in_array('cash', $selected) && in_array('upi', $selected)) {
        $showCashInput = true;
        } elseif (in_array('upi', $selected) && in_array('card', $selected)) {
        $showUpiInput = true;
        } elseif (in_array('cash', $selected) && in_array('card', $selected)) {
        $showCashInput = true;
        }
        }
        @endphp

        @if ($showCashInput)
        <x-filament::section class="bg-white shadow-sm border border-gray-200 rounded-lg">
            <x-filament::input.wrapper class="col-span-1">
                <x-filament::input type="text" label="Cash Amount" wire:model="paid_amount"
                    placeholder="Enter Cash Amount" class="block w-full rounded-md border-gray-300" />
            </x-filament::input.wrapper>
        </x-filament::section>
        @endif

        @if ($showUpiInput)
        <x-filament::section class="bg-white shadow-sm border border-gray-200 rounded-lg">
            <x-filament::input.wrapper class="col-span-1">
                <x-filament::input type="text" label="UPI Amount" wire:model="upi_amount" placeholder="Enter UPI Amount"
                    class="block w-full rounded-md border-gray-300" />
            </x-filament::input.wrapper>
        </x-filament::section>
        @endif


    </div>

    <div class="text-right flex items-center justify-end">
        <label for="total_discount">Discount in % : &nbsp;</label>
        <x-filament::input.wrapper class="w-40">
            <x-filament::input type="text" wire:model.lazy="total_discount" wire:change="updateTotalDiscount" />
        </x-filament::input.wrapper>
    </div>



    <div class="text-right">
        <div class="flex items-center justify-end gap-2">
            <span class="font-medium text-lg">Total Discount in %:</span>
            <span class="text-lg">{{ number_format($this->total_discount, 2) }}</span>
        </div>
        <div class="flex items-center justify-end gap-2">
            <span class="font-medium text-lg">Total Discount Amount:</span>
            <span class="text-lg">{{ number_format($this->discountedAmount, 2) }}</span>
        </div>
        <div class="flex items-center justify-end gap-2">
            <span class="font-medium text-lg">Total:</span>
            <span class="text-lg">{{ number_format($this->total_amount, 2) }}</span>
        </div>

    </div>



    <!-- Actions Buttons -->
    <div class="grid grid-flow-col auto-cols-fr gap-2 mt-4">
        <x-filament::button wire:click="clearCart" color="danger" size="sm" icon="heroicon-o-trash"
            class="flex items-center justify-center">
            Clear Cart
        </x-filament::button>

        <x-filament::button wire:click="createOrder" color="success" size="sm" icon="heroicon-o-bookmark"
            class="flex items-center justify-center">
            Save Order
        </x-filament::button>

        <x-filament::button wire:click="createOrderAndPrint" color="primary" size="sm" icon="heroicon-o-printer"
            class="flex items-center justify-center bg-blue-500 hover:bg-blue-700">
            Create Order & Print
        </x-filament::button>

    </div>
    @endif


    <!-- JavaScript for Focus and Event Listeners -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    const addButton = document.getElementById('add-item');
                    if (addButton) {
                        addButton.click();
                    }
                }
            });

            Livewire.on('downloadInvoice', url => {
                window.open(url, '_blank');
            });
        });
    </script>
</x-filament::page>