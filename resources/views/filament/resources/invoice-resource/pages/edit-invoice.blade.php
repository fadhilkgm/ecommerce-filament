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