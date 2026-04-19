@livewire(
    App\Filament\Resources\CustomerResource\RelationManagers\PropertiesRelationManager::class,
    ['ownerRecord' => $record, 'pageClass' => $this::class],
    key('properties-' . $record->getKey()),
)
