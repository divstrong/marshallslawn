<?php

namespace App\Filament\Resources\JobResource\Pages;

use App\Filament\Resources\JobResource;
use App\Services\JobFromFormCreator;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateJob extends CreateRecord
{
    protected static string $resource = JobResource::class;

    /**
     * A one-time job creates a single record; a recurring job spawns a template
     * and materialises its occurrences (issue #13). Both, along with the Services
     * tab lines, are handled by the shared creator so this page and the customer
     * Jobs-tab modal behave identically (issue #54).
     */
    protected function handleRecordCreation(array $data): Model
    {
        $result = app(JobFromFormCreator::class)->create($data);

        if ($result['created']->count() > 1) {
            Notification::make()
                ->title('Recurring job created')
                ->body($result['created']->count() . ' occurrence(s) scheduled.')
                ->success()
                ->send();
        }

        return $result['job'];
    }
}
