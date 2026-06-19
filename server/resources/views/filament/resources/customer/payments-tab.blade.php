@livewire(
    App\Filament\Resources\CustomerResource\RelationManagers\PaymentsRelationManager::class,
    ['ownerRecord' => $record, 'pageClass' => $this::class],
    key('payments-' . $record->getKey()),
)
