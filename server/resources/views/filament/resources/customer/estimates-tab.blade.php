@livewire(
    App\Filament\Resources\CustomerResource\RelationManagers\EstimatesRelationManager::class,
    ['ownerRecord' => $record, 'pageClass' => $this::class],
    key('estimates-' . $record->getKey()),
)
