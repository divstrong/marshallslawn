@livewire(
    App\Filament\Resources\ServiceResource\RelationManagers\OptionsRelationManager::class,
    ['ownerRecord' => $record, 'pageClass' => $this::class],
    key('options-' . $record->getKey()),
)
