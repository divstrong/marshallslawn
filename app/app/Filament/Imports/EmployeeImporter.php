<?php

namespace App\Filament\Imports;

use App\Models\Employee;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class EmployeeImporter extends Importer
{
    protected static ?string $model = Employee::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Employee')
                ->guess(['Employee'])
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->example('John Doe'),
            ImportColumn::make('email')
                ->label('Main Email')
                ->guess(['Main Email'])
                ->rules(['nullable', 'email', 'max:255'])
                ->example('john@example.com'),
            ImportColumn::make('phone')
                ->label('Main Phone')
                ->guess(['Main Phone'])
                ->rules(['nullable', 'max:255'])
                ->example('804-555-1234'),
            ImportColumn::make('mobile_phone')
                ->label('Mobile')
                ->guess(['Mobile'])
                ->rules(['nullable', 'max:255'])
                ->example('804-555-5678'),
            ImportColumn::make('alt_phone')
                ->label('Alt. Phone')
                ->guess(['Alt. Phone', 'Alt Phone'])
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('address')
                ->rules(['nullable', 'max:255'])
                ->example('123 Main St'),
            ImportColumn::make('city')
                ->rules(['nullable', 'max:255'])
                ->example('Richmond'),
            ImportColumn::make('state')
                ->rules(['nullable', 'max:255'])
                ->example('VA'),
            ImportColumn::make('zip')
                ->rules(['nullable', 'max:255'])
                ->example('23223'),
            ImportColumn::make('date_of_birth')
                ->label('Date of Birth')
                ->rules(['nullable', 'date'])
                ->example('02/10/1985'),
            ImportColumn::make('notes')
                ->rules(['nullable']),
        ];
    }

    public function resolveRecord(): ?Employee
    {
        $criteria = [];

        if (! empty($this->data['name'])) {
            $criteria['name'] = $this->data['name'];
        }

        if (! empty($this->data['email'])) {
            $criteria['email'] = $this->data['email'];
        }

        if (! empty($this->data['phone'])) {
            $criteria['phone'] = $this->data['phone'];
        }

        if (empty($criteria)) {
            return new Employee();
        }

        return Employee::firstOrNew($criteria);
    }

    protected function beforeSave(): void
    {
        if (! $this->record->exists) {
            $this->record->role = 'field';
            $this->record->status = $this->record->status ?? 'active';
        }

        // Split full name into first/last if not already set
        if (! empty($this->record->name) && empty($this->record->first_name)) {
            $parts = explode(' ', $this->record->name, 2);
            $this->record->first_name = $parts[0];
            $this->record->last_name = $parts[1] ?? '';
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your employee import has completed. ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
