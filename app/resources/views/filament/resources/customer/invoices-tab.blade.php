@livewire(
    App\Filament\Resources\CustomerResource\RelationManagers\InvoicesRelationManager::class,
    ['ownerRecord' => $record, 'pageClass' => $this::class],
    key('invoices-' . $record->getKey()),
)
