<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Property;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportClientsProperties extends Command
{
    protected $signature = 'import:clients-properties {file? : Path to the Clients-Properties XLSX file}';
    protected $description = 'Purge all customers/properties and re-import from the Clients-Properties XLSX';

    public function handle(): int
    {
        $file = $this->argument('file')
            ?? base_path('Clients-Properties -  - 2026-04-07..xlsx');

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        // Confirm destructive action
        if (! $this->confirm('This will DELETE all existing customers and properties, then re-import from the spreadsheet. Continue?')) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        // Purge existing records (properties cascade via FK)
        $this->info('Purging existing customers and properties...');
        $propCount = Property::count();
        $custCount = Customer::count();
        Property::query()->delete();
        Customer::query()->delete();
        $this->info("  Deleted {$custCount} customers and {$propCount} properties.");

        // Read spreadsheet
        $this->info('Reading XLSX file...');
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getSheet(0);
        $data = $sheet->toArray(null, true, true, true);

        if (count($data) < 2) {
            $this->error('Spreadsheet appears empty.');
            return self::FAILURE;
        }

        // Build header map from row 1
        $headers = $data[1];
        unset($data[1]);

        $bar = $this->output->createProgressBar(count($data));
        $imported = 0;
        $propertiesCreated = 0;

        foreach ($data as $rowNum => $row) {
            // Map columns by header name
            $r = [];
            foreach ($headers as $col => $headerName) {
                $r[$headerName] = $row[$col];
            }

            $firstName = trim($r['FirstName'] ?? '');
            $lastName = trim($r['LastName'] ?? '');

            // Skip rows without a name
            if (! $firstName && ! $lastName) {
                $bar->advance();
                continue;
            }

            // Determine phone (prefer CellPhone > HomePhone > WorkPhone)
            $phone = $r['CellPhone'] ?? $r['HomePhone'] ?? $r['WorkPhone'] ?? null;
            $phone = $phone ? trim($phone) : null;

            // Map status from CustomerType column
            $statusMap = [
                'Client'   => 'active',
                'Lead'     => 'lead',
                'Inactive' => 'inactive',
            ];
            $status = $statusMap[$r['CustomerType'] ?? ''] ?? 'active';

            // Determine customer type from AccountType column
            $customerType = ! empty($r['AccountType']) ? trim($r['AccountType']) : null;

            $customer = Customer::create([
                'legacy_id'      => $r['UserName'] ?? null,
                'company_name'   => ! empty($r['NameOnInvoice']) ? trim($r['NameOnInvoice']) : null,
                'first_name'     => $firstName,
                'last_name'      => $lastName,
                'email'          => ! empty($r['Email']) ? trim($r['Email']) : null,
                'phone'          => $phone,
                'address'        => ! empty($r['BillingAddress']) ? trim($r['BillingAddress']) : null,
                'city'           => ! empty($r['BillingCity']) ? trim($r['BillingCity']) : null,
                'state'          => ! empty($r['BillingState']) ? trim($r['BillingState']) : null,
                'zip'            => ! empty($r['BillingZip']) ? trim($r['BillingZip']) : null,
                'status'         => $status,
                'customer_type'  => $customerType,
                'account_number' => ! empty($r['AccountNumber']) ? trim($r['AccountNumber']) : null,
                'division'       => ! empty($r['Division']) ? trim($r['Division']) : null,
                'map_code'       => ! empty($r['MapCode']) ? trim($r['MapCode']) : null,
                'source'         => ! empty($r['Source']) ? trim($r['Source']) : null,
                'notes'          => ! empty($r['OfficeNotes']) ? trim($r['OfficeNotes']) : null,
            ]);

            // Create property from Physical Address columns
            $physAddr = ! empty($r['PhysicalAddress']) ? trim($r['PhysicalAddress']) : null;
            if ($physAddr) {
                Property::create([
                    'customer_id' => $customer->id,
                    'address'     => $physAddr,
                    'city'        => ! empty($r['PhysicalCity']) ? trim($r['PhysicalCity']) : null,
                    'state'       => ! empty($r['PhysicalState']) ? trim($r['PhysicalState']) : null,
                    'zip'         => ! empty($r['PhysicalZip']) ? trim($r['PhysicalZip']) : null,
                    'is_primary'  => true,
                ]);
                $propertiesCreated++;
            }

            $imported++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Imported {$imported} customers with {$propertiesCreated} properties.");

        return self::SUCCESS;
    }
}
