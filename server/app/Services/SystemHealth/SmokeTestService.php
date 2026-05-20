<?php

namespace App\Services\SystemHealth;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Package;
use App\Models\Property;
use App\Models\Service;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Throwable;

class SmokeTestService
{
    public function runAll(): array
    {
        return [
            $this->run('Customer CRUD', fn () => $this->customerCrud()),
            $this->run('Property CRUD', fn () => $this->propertyCrud()),
            $this->run('Estimate CRUD', fn () => $this->estimateCrud()),
            $this->run('Invoice CRUD', fn () => $this->invoiceCrud()),
            $this->run('Job CRUD', fn () => $this->jobCrud()),
            $this->run('Vendor CRUD', fn () => $this->vendorCrud()),
            $this->run('Service CRUD', fn () => $this->serviceCrud()),
            $this->run('Package CRUD', fn () => $this->packageCrud()),
            $this->run('Employee CRUD', fn () => $this->employeeCrud()),
        ];
    }

    protected function run(string $name, callable $fn): array
    {
        $start = microtime(true);
        $steps = [];

        try {
            DB::beginTransaction();
            $steps = $fn();
            DB::rollBack();

            $failed = collect($steps)->firstWhere('passed', false);

            return [
                'name' => $name,
                'passed' => ! $failed,
                'message' => $failed
                    ? "Failed at step: {$failed['step']} — {$failed['message']}"
                    : 'All ' . count($steps) . ' steps passed (create, read, update, delete)',
                'steps' => $steps,
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            ];
        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return [
                'name' => $name,
                'passed' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'steps' => $steps,
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            ];
        }
    }

    protected function step(string $label, bool $passed, string $message = ''): array
    {
        return ['step' => $label, 'passed' => $passed, 'message' => $message];
    }

    protected function customerCrud(): array
    {
        $steps = [];
        $customer = Customer::create([
            'first_name' => '_smoke',
            'last_name' => 'test',
            'email' => 'smoke_' . uniqid() . '@healthcheck.local',
            'status' => 'active',
        ]);
        $steps[] = $this->step('create', (bool) $customer->id, "id={$customer->id}");

        $found = Customer::find($customer->id);
        $steps[] = $this->step('read', $found !== null);

        $found->update(['last_name' => 'updated']);
        $steps[] = $this->step('update', $found->fresh()->last_name === 'updated');

        $found->delete();
        $steps[] = $this->step('delete', Customer::find($customer->id) === null);

        return $steps;
    }

    protected function propertyCrud(): array
    {
        $steps = [];
        $customer = Customer::create([
            'first_name' => '_smoke',
            'last_name' => 'prop',
            'email' => 'smoke_' . uniqid() . '@healthcheck.local',
        ]);

        $property = Property::create([
            'customer_id' => $customer->id,
            'address' => '123 Healthcheck Ln',
            'city' => 'Testville',
            'state' => 'IN',
            'zip' => '46000',
        ]);
        $steps[] = $this->step('create', (bool) $property->id, "id={$property->id}");

        $steps[] = $this->step('read', Property::find($property->id) !== null);

        $property->update(['city' => 'Updated']);
        $steps[] = $this->step('update', $property->fresh()->city === 'Updated');

        $property->delete();
        $steps[] = $this->step('delete', Property::find($property->id) === null);

        return $steps;
    }

    protected function estimateCrud(): array
    {
        $steps = [];
        $customer = Customer::create([
            'first_name' => '_smoke',
            'last_name' => 'est',
            'email' => 'smoke_' . uniqid() . '@healthcheck.local',
        ]);

        $estimate = Estimate::create([
            'customer_id' => $customer->id,
            'status' => 'draft',
            'subtotal' => 100,
            'tax' => 7,
            'total' => 107,
        ]);
        $steps[] = $this->step('create', (bool) $estimate->id, "number={$estimate->estimate_number}");

        $steps[] = $this->step('read', Estimate::find($estimate->id) !== null);

        $estimate->update(['status' => 'sent']);
        $steps[] = $this->step('update', $estimate->fresh()->status === 'sent');

        $estimate->delete();
        $steps[] = $this->step('delete', Estimate::find($estimate->id) === null);

        return $steps;
    }

    protected function invoiceCrud(): array
    {
        $steps = [];
        $customer = Customer::create([
            'first_name' => '_smoke',
            'last_name' => 'inv',
            'email' => 'smoke_' . uniqid() . '@healthcheck.local',
        ]);

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'status' => 'draft',
            'subtotal' => 250,
            'tax' => 17.5,
            'total' => 267.5,
        ]);
        $steps[] = $this->step('create', (bool) $invoice->id, "number={$invoice->invoice_number}");

        $steps[] = $this->step('read', Invoice::find($invoice->id) !== null);

        $invoice->update(['status' => 'sent']);
        $steps[] = $this->step('update', $invoice->fresh()->status === 'sent');

        $invoice->delete();
        $steps[] = $this->step('delete', Invoice::find($invoice->id) === null);

        return $steps;
    }

    protected function jobCrud(): array
    {
        $steps = [];
        $customer = Customer::create([
            'first_name' => '_smoke',
            'last_name' => 'job',
            'email' => 'smoke_' . uniqid() . '@healthcheck.local',
        ]);
        $property = Property::create([
            'customer_id' => $customer->id,
            'address' => '1 Job Ln',
            'city' => 'Testville',
            'state' => 'IN',
            'zip' => '46000',
        ]);

        $job = Job::create([
            'customer_id' => $customer->id,
            'property_id' => $property->id,
            'title' => '_smoke job',
            'status' => 'scheduled',
        ]);
        $steps[] = $this->step('create', (bool) $job->id, "id={$job->id}");

        $steps[] = $this->step('read', Job::find($job->id) !== null);

        $job->update(['status' => 'completed']);
        $steps[] = $this->step('update', $job->fresh()->status === 'completed');

        $job->delete();
        $steps[] = $this->step('delete', Job::find($job->id) === null);

        return $steps;
    }

    protected function vendorCrud(): array
    {
        $steps = [];
        $vendor = Vendor::create([
            'name' => '_smoke vendor ' . uniqid(),
            'company' => 'SmokeCo',
            'status' => 'active',
        ]);
        $steps[] = $this->step('create', (bool) $vendor->id, "id={$vendor->id}");

        $steps[] = $this->step('read', Vendor::find($vendor->id) !== null);

        $vendor->update(['company' => 'Updated']);
        $steps[] = $this->step('update', $vendor->fresh()->company === 'Updated');

        $vendor->delete();
        $steps[] = $this->step('delete', Vendor::find($vendor->id) === null);

        return $steps;
    }

    protected function serviceCrud(): array
    {
        $steps = [];
        $service = Service::create([
            'name' => '_smoke service ' . uniqid(),
            'code' => 'SMK' . uniqid(),
            'category' => 'smoke',
            'default_price' => 99.99,
            'unit' => 'each',
            'is_active' => true,
        ]);
        $steps[] = $this->step('create', (bool) $service->id, "id={$service->id}");

        $steps[] = $this->step('read', Service::find($service->id) !== null);

        $service->update(['default_price' => 49.99]);
        $steps[] = $this->step('update', (float) $service->fresh()->default_price === 49.99);

        $service->delete();
        $steps[] = $this->step('delete', Service::find($service->id) === null);

        return $steps;
    }

    protected function packageCrud(): array
    {
        $steps = [];
        $package = Package::create([
            'name' => '_smoke package ' . uniqid(),
            'price' => 199.99,
            'is_active' => true,
        ]);
        $steps[] = $this->step('create', (bool) $package->id, "id={$package->id}");

        $steps[] = $this->step('read', Package::find($package->id) !== null);

        $package->update(['price' => 249.99]);
        $steps[] = $this->step('update', (float) $package->fresh()->price === 249.99);

        $package->delete();
        $steps[] = $this->step('delete', Package::find($package->id) === null);

        return $steps;
    }

    protected function employeeCrud(): array
    {
        $steps = [];
        $employee = Employee::create([
            'name' => '_smoke emp',
            'first_name' => '_smoke',
            'last_name' => 'emp',
            'email' => 'smoke_' . uniqid() . '@healthcheck.local',
        ]);
        $steps[] = $this->step('create', (bool) $employee->id, "id={$employee->id}");

        $steps[] = $this->step('read', Employee::find($employee->id) !== null);

        $employee->update(['last_name' => 'updated']);
        $steps[] = $this->step('update', $employee->fresh()->last_name === 'updated');

        $employee->delete();
        $steps[] = $this->step('delete', Employee::find($employee->id) === null);

        return $steps;
    }
}
