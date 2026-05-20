<?php

namespace App\Console\Commands;

use App\Models\Crew;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Service;
use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use ZipArchive;

class ImportLegacyData extends Command
{
    protected $signature = 'import:legacy {file? : Path to ODS export file}';
    protected $description = 'Import legacy data from ODS export file into the database';

    private DOMXPath $xpath;

    public function handle(): int
    {
        $file = $this->argument('file') ?? base_path('Company -Export- 2026-02-24..ods');

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $this->info('Reading ODS file...');
        $sheets = $this->parseOds($file);

        if (empty($sheets)) {
            $this->error('Could not parse ODS file.');
            return self::FAILURE;
        }

        $this->info('Found sheets: ' . implode(', ', array_keys($sheets)));

        if (isset($sheets['Employees'])) {
            $this->importEmployees($sheets['Employees']);
        }

        if (isset($sheets['Crews'])) {
            $this->importCrews($sheets['Crews']);
            $this->assignForemen($sheets['Crews']);
        }

        if (isset($sheets['Clients'])) {
            $this->importClients($sheets['Clients']);
        }

        if (isset($sheets['Services'])) {
            $this->importServices($sheets['Services']);
        }

        $this->newLine();
        $this->info('Legacy data import complete!');

        return self::SUCCESS;
    }

    private function assignForemen(array $rows): void
    {
        $this->info('Assigning crew foremen from crew descriptions...');

        // Map of crew code/description keywords to employee first names
        $foremanMap = [
            'Dave' => 'David',
            'Moises' => 'Moises',
            'Julio' => 'Julio',
            'Valentin' => 'Valentin',
            'Ventura' => 'Nelson',    // Nelson Ventura
            'Antonio' => 'Antonio',
            'Jonathan' => 'Jonathan',  // Jonathan Rodas Ventura
            'Pablo' => 'Pablo',
            'Henry' => 'Henry',        // Henry Titsa Titsa
            'Trey' => 'Trey',
            'Michael' => 'Michael',
            'Harner' => 'David',       // David Harner (match by last name)
            'Will' => 'Will',
        ];

        $assigned = 0;
        foreach ($rows as $row) {
            $crewCode = $row['CrewCode'] ?? '';
            $description = $row['Description'] ?? '';
            $legacyId = $row['CrewID'] ?? null;

            if (! $legacyId) {
                continue;
            }

            $crew = Crew::where('legacy_id', $legacyId)->first();
            if (! $crew || $crew->foreman_id) {
                continue;
            }

            // Try matching by crew code first, then description
            $foreman = null;
            foreach ($foremanMap as $keyword => $firstName) {
                if (stripos($crewCode, $keyword) !== false || stripos($description, $keyword) !== false) {
                    // Special case: "Harner" matches last name
                    if ($keyword === 'Harner') {
                        $foreman = Employee::where('last_name', 'like', '%Harner%')->first();
                    } elseif ($keyword === 'Dave') {
                        // Match "David Marshall" (not SA version)
                        $foreman = Employee::where('first_name', 'David')
                            ->where('last_name', 'Marshall')
                            ->whereNot('last_name', 'like', '%(SA)%')
                            ->first();
                    } elseif ($keyword === 'Ventura') {
                        $foreman = Employee::where('last_name', 'Ventura')
                            ->where('first_name', 'Nelson')
                            ->first();
                    } elseif ($keyword === 'Jonathan') {
                        $foreman = Employee::where('first_name', 'Jonathan')
                            ->where('last_name', 'like', 'Rodas%')
                            ->first();
                    } elseif ($keyword === 'Henry') {
                        $foreman = Employee::where('first_name', 'Henry')
                            ->where('last_name', 'like', 'Titsa%')
                            ->first();
                    } else {
                        $foreman = Employee::where('first_name', $firstName)->first();
                    }
                    break;
                }
            }

            if ($foreman) {
                $crew->update(['foreman_id' => $foreman->id]);
                $this->line("  Assigned {$foreman->name} as foreman of {$crewCode}");
                $assigned++;
            }
        }

        $this->info("Assigned {$assigned} foremen.");
    }

    private function importClients(array $rows): void
    {
        $this->info('Importing Clients -> Customers...');
        $bar = $this->output->createProgressBar(count($rows));

        $imported = 0;
        foreach ($rows as $row) {
            $legacyId = $row['ClientID'] ?? null;
            if (! $legacyId) {
                $bar->advance();
                continue;
            }

            // Parse CityStZip: "Glen Allen, VA 23059"
            $city = null;
            $state = null;
            $zip = null;
            $cityStZip = $row['CityStZip'] ?? '';
            if (preg_match('/^(.+),\s*([A-Z]{2})\s+(\d{5}(?:-\d{4})?)$/', $cityStZip, $matches)) {
                $city = trim($matches[1]);
                $state = $matches[2];
                $zip = $matches[3];
            } elseif (! empty($cityStZip)) {
                $city = $cityStZip;
            }

            // Map CustomerStatus to our status values
            $statusMap = [
                'Client' => 'active',
                'Lead' => 'lead',
                'Inactive' => 'inactive',
            ];
            $status = $statusMap[$row['CustomerStatus'] ?? ''] ?? 'lead';

            Customer::updateOrCreate(
                ['legacy_id' => $legacyId],
                [
                    'company_name' => $row['ClientName'] ?? null,
                    'first_name' => $row['ContactFirstName'] ?? '',
                    'last_name' => $row['ContactLastName'] ?? '',
                    'address' => $row['Address'] ?? null,
                    'city' => $city,
                    'state' => $state,
                    'zip' => $zip,
                    'status' => $status,
                    'customer_type' => $row['CustomerType'] ?? null,
                    'account_number' => $row['AccountNumber'] ?? null,
                    'division' => $row['Division'] ?? null,
                    'map_code' => $row['MapCode'] ?? null,
                    'list_id' => $row['ListID'] ?? null,
                ]
            );
            $imported++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Imported {$imported} customers.");
    }

    private function importCrews(array $rows): void
    {
        $this->info('Importing Crews...');
        $bar = $this->output->createProgressBar(count($rows));

        $imported = 0;
        foreach ($rows as $row) {
            $legacyId = $row['CrewID'] ?? null;
            if (! $legacyId) {
                $bar->advance();
                continue;
            }

            Crew::updateOrCreate(
                ['legacy_id' => $legacyId],
                [
                    'code' => $row['CrewCode'] ?? null,
                    'name' => $row['Description'] ?? $row['CrewCode'] ?? 'Unknown',
                    'status' => 'active',
                    'division' => $row['Division'] ?? null,
                ]
            );
            $imported++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Imported {$imported} crews.");
    }

    private function importServices(array $rows): void
    {
        $this->info('Importing Services...');
        $bar = $this->output->createProgressBar(count($rows));

        $imported = 0;
        foreach ($rows as $row) {
            $legacyId = $row['ServiceID'] ?? null;
            if (! $legacyId) {
                $bar->advance();
                continue;
            }

            Service::updateOrCreate(
                ['legacy_id' => $legacyId],
                [
                    'name' => $row['Name'] ?? 'Unknown',
                    'full_name' => $row['FullName'] ?? null,
                    'description' => $row['Description'] ?? null,
                    'category' => $row['Category'] ?? 'general',
                    'is_active' => true,
                    'list_id' => $row['ListID'] ?? null,
                ]
            );
            $imported++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Imported {$imported} services.");
    }

    private function importEmployees(array $rows): void
    {
        $this->info('Importing Employees...');
        $bar = $this->output->createProgressBar(count($rows));

        $imported = 0;
        foreach ($rows as $row) {
            $legacyId = $row['EmployeeID'] ?? null;
            if (! $legacyId) {
                $bar->advance();
                continue;
            }

            $firstName = $row['FirstName'] ?? '';
            $lastName = $row['LastName'] ?? '';

            Employee::updateOrCreate(
                ['legacy_id' => $legacyId],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'name' => trim("{$firstName} {$lastName}") ?: 'Unknown',
                    'email' => null,
                    'status' => 'active',
                    'division' => $row['Division'] ?? null,
                    'list_id' => $row['ListID'] ?? null,
                ]
            );
            $imported++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Imported {$imported} employees.");
    }

    /**
     * Parse an ODS file and return an associative array of sheets.
     * Each sheet is an array of rows, where each row is keyed by the header column names.
     */
    private function parseOds(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $xml = $zip->getFromName('content.xml');
        $zip->close();

        if (! $xml) {
            return [];
        }

        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $this->xpath = new DOMXPath($dom);
        $this->xpath->registerNamespace('table', 'urn:oasis:names:tc:opendocument:xmlns:table:1.0');
        $this->xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');

        $sheets = [];
        $tables = $this->xpath->query('//table:table');

        foreach ($tables as $table) {
            $sheetName = $table->getAttribute('table:name');
            $rows = $this->xpath->query('.//table:table-row', $table);
            $headers = [];
            $sheetData = [];

            $isFirstRow = true;
            foreach ($rows as $row) {
                $cellData = $this->getRowData($row);

                if (empty($cellData)) {
                    continue;
                }

                if ($isFirstRow) {
                    $headers = $cellData;
                    $isFirstRow = false;
                    continue;
                }

                // Build associative row from headers
                $assocRow = [];
                foreach ($headers as $i => $header) {
                    $assocRow[$header] = $cellData[$i] ?? null;
                }

                // Skip rows where the ID column is empty
                $firstValue = reset($assocRow);
                if (empty($firstValue)) {
                    continue;
                }

                $sheetData[] = $assocRow;
            }

            $sheets[$sheetName] = $sheetData;
        }

        return $sheets;
    }

    private function getRowData($row): array
    {
        $cells = $this->xpath->query('.//table:table-cell', $row);
        $data = [];

        foreach ($cells as $cell) {
            $repeat = $cell->getAttribute('table:number-columns-repeated');
            $text = '';
            $ps = $this->xpath->query('.//text:p', $cell);
            foreach ($ps as $p) {
                $text .= $p->textContent;
            }

            $count = $repeat ? (int) $repeat : 1;

            // Don't expand large runs of empty cells
            if ($text === '' && $count > 10) {
                break;
            }

            for ($i = 0; $i < $count; $i++) {
                $data[] = $text;
            }
        }

        // Trim trailing empty cells
        while (count($data) > 0 && end($data) === '') {
            array_pop($data);
        }

        return $data;
    }
}
