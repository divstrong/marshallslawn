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
     * Seed the customer (and their primary property) from ?customer_id=, so the
     * "New Job" button on a customer's page lands on a form already pointed at
     * them. Filament's own fill runs first, then the query string wins.
     */
    protected function fillForm(): void
    {
        parent::fillForm();

        $customerId = request()->integer('customer_id');
        if ($customerId <= 0 || ! \App\Models\Customer::whereKey($customerId)->exists()) {
            return;
        }

        $propertyId = \App\Models\Property::where('customer_id', $customerId)
            ->orderByDesc('is_primary')
            ->orderBy('address')
            ->value('id');

        // Write into the form's state array rather than re-filling it: getState()
        // validates, and this form is deliberately still empty at this point.
        $this->data['customer_id'] = $customerId;
        $this->data['property_id'] = $propertyId;
    }

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
