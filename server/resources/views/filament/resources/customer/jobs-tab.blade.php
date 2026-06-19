@livewire(
    App\Filament\Resources\CustomerResource\RelationManagers\JobsRelationManager::class,
    ['ownerRecord' => $record, 'pageClass' => $this::class],
    key('jobs-' . $record->getKey()),
)
