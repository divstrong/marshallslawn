<x-filament-panels::page>
    {{-- ?customer_id= pre-selects the customer, so "New Invoice" reached from a
         customer's page opens the builder already pointed at them. --}}
    @livewire('invoice-builder', ['customerId' => request()->integer('customer_id') ?: null])
</x-filament-panels::page>
