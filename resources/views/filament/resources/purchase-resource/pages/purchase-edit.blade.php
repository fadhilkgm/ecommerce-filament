<x-filament-panels::page>
    <form wire:submit.prevent="submit">
        {{ $this->form }}
        <x-filament::button type="submit" id="add-item" class="mt-2" color="success">
            Add Item
        </x-filament::button>
    </form>

    <!-- Cart Table -->
    @if ($this->purchase_id)
    <div class="mt-6">
        {{ $this->table }}
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
</x-filament-panels::page>