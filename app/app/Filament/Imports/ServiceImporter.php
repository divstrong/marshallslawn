<?php

namespace App\Filament\Imports;

use App\Models\Service;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ServiceImporter extends Importer
{
    protected static ?string $model = Service::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Service Name')
                ->guess(['ServiceName'])
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->example('Lawn Mowing'),
            ImportColumn::make('code')
                ->label('Code')
                ->guess(['Code'])
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('parent_service')
                ->label('Parent Service')
                ->guess(['ParentService'])
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('default_price')
                ->label('Default Rate')
                ->guess(['DefaultRate'])
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0'])
                ->example('50.00'),
            ImportColumn::make('minimum_amount')
                ->label('Minimum Amount')
                ->guess(['MinimumAmount'])
                ->numeric(decimalPlaces: 2)
                ->rules(['nullable', 'numeric', 'min:0']),
            ImportColumn::make('service_mode')
                ->label('Service Mode')
                ->guess(['ServiceMode'])
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('invoice_description')
                ->label('Invoice Description')
                ->guess(['InvoiceDescription'])
                ->rules(['nullable']),
            ImportColumn::make('estimate_description')
                ->label('Estimate Description')
                ->guess(['EstimateDescription'])
                ->rules(['nullable']),
            ImportColumn::make('is_active')
                ->label('Active')
                ->guess(['Active'])
                ->boolean()
                ->rules(['nullable']),
            ImportColumn::make('track_chemicals')
                ->label('Track Chemicals')
                ->guess(['TrackChemicals'])
                ->boolean()
                ->rules(['nullable']),
            ImportColumn::make('show_in_snow')
                ->label('Show In Snow')
                ->guess(['ShowInSnow'])
                ->boolean()
                ->rules(['nullable']),
        ];
    }

    public function resolveRecord(): ?Service
    {
        return Service::firstOrNew([
            'name' => $this->data['name'],
        ]);
    }

    protected function beforeSave(): void
    {
        if (! $this->record->exists) {
            $this->record->category = $this->record->category ?? 'General';
            $this->record->unit = 'per_service';
        }

        // Map service_mode from CSV to unit
        if (! empty($this->data['service_mode'])) {
            $modeMap = [
                'PerUnit' => 'per_unit',
                'FlatRate' => 'flat_rate',
                'Hourly' => 'hourly',
            ];
            $this->record->unit = $modeMap[$this->data['service_mode']] ?? 'per_service';
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your service import has completed. ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
