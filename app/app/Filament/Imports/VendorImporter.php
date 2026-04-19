<?php

namespace App\Filament\Imports;

use App\Models\Vendor;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class VendorImporter extends Importer
{
    protected static ?string $model = Vendor::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Vendor')
                ->guess(['Vendor'])
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->example('ABC Supply'),
            ImportColumn::make('status')
                ->label('Active Status')
                ->guess(['Active Status'])
                ->rules(['nullable', 'max:255'])
                ->example('Active'),
            ImportColumn::make('company')
                ->guess(['Company'])
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('first_name')
                ->label('First Name')
                ->guess(['First Name'])
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('last_name')
                ->label('Last Name')
                ->guess(['Last Name'])
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('address_1')
                ->label('Address Line 1')
                ->guess(['Bill from 1'])
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('address_2')
                ->label('Address Line 2')
                ->guess(['Bill from 2'])
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('primary_contact')
                ->label('Primary Contact')
                ->guess(['Primary Contact'])
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('secondary_contact')
                ->label('Secondary Contact')
                ->guess(['Secondary Contact'])
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('job_title')
                ->label('Job Title')
                ->guess(['Job Title'])
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('phone')
                ->label('Main Phone')
                ->guess(['Main Phone'])
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('fax')
                ->guess(['Fax'])
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('alt_phone')
                ->label('Alt. Phone')
                ->guess(['Alt. Phone', 'Alt Phone'])
                ->rules(['nullable', 'max:255']),
        ];
    }

    public function resolveRecord(): ?Vendor
    {
        return Vendor::firstOrNew([
            'name' => $this->data['name'],
        ]);
    }

    protected function beforeSave(): void
    {
        // Normalize status from CSV ("Active"/"Inactive") to our format
        if (! empty($this->data['status'])) {
            $this->record->status = strtolower($this->data['status']) === 'active' ? 'active' : 'inactive';
        } elseif (! $this->record->exists) {
            $this->record->status = 'active';
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your vendor import has completed. ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
