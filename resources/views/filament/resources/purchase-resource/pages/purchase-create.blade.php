<x-filament-panels::page>
    <form wire:submit.prevent="submit">
        {{ $this->form }}
        <x-filament::button type="submit" id="add-item" class="mt-2" color="success">
            Add Item
        </x-filament::button>
    </form>

    <!-- Cart Table -->

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
                <x-filament::button icon="heroicon-o-banknotes" wire:click="$set('payment_method', 'cash')"
                    color="{{ $payment_method === 'cash' ? 'primary' : 'info' }}" :outlined="$payment_method !== 'cash'"
                    class="py-2 px-4 rounded-md transition-all duration-300">
                    Cash
                </x-filament::button>

                <x-filament::button wire:click="$set('payment_method', 'credit')" icon="heroicon-o-credit-card"
                    color="{{ $payment_method === 'credit' ? 'primary' : 'info' }}"
                    :outlined="$payment_method !== 'credit'" class="py-2 px-4 rounded-md transition-all duration-300">
                    Credit
                </x-filament::button>
            </div>
        </x-filament::section>

        <!-- Conditional Reference Number Input -->
        @if ($this->payment_method == 'cash')
        <x-filament::section class="bg-white shadow-sm border border-gray-200 rounded-lg">
            <div class="grid grid-flow-col auto-cols-fr gap-2">

                <x-filament::input.wrapper class="col-span-1">
                    <x-filament::input type="text" label="Cash Amount" id="paid_amount" wire:model="paid_amount"
                        placeholder="Enter Paid Amount"
                        class="block w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 shadow-sm" />
                </x-filament::input.wrapper>
            </div>
        </x-filament::section>
        @endif
    </div>


    <!-- Actions Buttons -->
    <div class="grid grid-flow-col auto-cols-fr gap-2 mt-4">
        <x-filament::button wire:click="clearCart" color="danger" size="sm" icon="heroicon-o-trash"
            class="flex items-center justify-center">
            Clear Items
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
</x-filament-panels::page>