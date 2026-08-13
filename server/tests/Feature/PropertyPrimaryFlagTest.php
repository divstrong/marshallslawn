<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyPrimaryFlagTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create(['first_name' => 'Rae', 'last_name' => 'Quill', 'status' => 'active']);
    }

    private function property(string $address, array $attributes = []): Property
    {
        return Property::create(array_merge([
            'customer_id' => $this->customer->id,
            'address' => $address,
        ], $attributes));
    }

    public function test_a_customers_only_property_is_primary_without_being_asked(): void
    {
        $only = $this->property('1 Sole St');

        $this->assertTrue($only->fresh()->is_primary);
    }

    public function test_a_second_property_does_not_claim_primary(): void
    {
        $first = $this->property('1 First St');
        $second = $this->property('2 Second St');

        $this->assertTrue($first->fresh()->is_primary);
        $this->assertFalse($second->fresh()->is_primary, 'the column no longer defaults to primary');
    }

    public function test_marking_a_second_property_primary_demotes_the_first(): void
    {
        $first = $this->property('1 First St');
        $second = $this->property('2 Second St');

        $second->update(['is_primary' => true]);

        $this->assertFalse($first->fresh()->is_primary, 'only one property may be primary');
        $this->assertTrue($second->fresh()->is_primary);
        $this->assertSame(1, $this->primaryCount());
    }

    public function test_creating_a_property_already_flagged_primary_demotes_the_incumbent(): void
    {
        $first = $this->property('1 First St');
        $second = $this->property('2 Second St', ['is_primary' => true]);

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);
        $this->assertSame(1, $this->primaryCount());
    }

    public function test_deleting_the_primary_promotes_the_earliest_survivor(): void
    {
        $first = $this->property('1 First St');
        $second = $this->property('2 Second St');
        $third = $this->property('3 Third St');

        $first->delete();

        $this->assertTrue($second->fresh()->is_primary, 'the earliest remaining property takes over');
        $this->assertFalse($third->fresh()->is_primary);
        $this->assertSame(1, $this->primaryCount());
    }

    public function test_deleting_a_secondary_property_leaves_the_primary_alone(): void
    {
        $first = $this->property('1 First St');
        $second = $this->property('2 Second St');

        $second->delete();

        $this->assertTrue($first->fresh()->is_primary);
        $this->assertSame(1, $this->primaryCount());
    }

    public function test_deleting_the_last_property_leaves_nothing_to_promote(): void
    {
        $only = $this->property('1 Sole St');

        $only->delete();

        $this->assertSame(0, $this->primaryCount());
    }

    public function test_each_customer_keeps_their_own_primary(): void
    {
        $mine = $this->property('1 Mine St');

        $other = Customer::create(['first_name' => 'Dev', 'last_name' => 'Oaks', 'status' => 'active']);
        $theirs = Property::create(['customer_id' => $other->id, 'address' => '1 Theirs St']);

        // Flagging one customer's property must not touch another customer's.
        $this->assertTrue($mine->fresh()->is_primary);
        $this->assertTrue($theirs->fresh()->is_primary);
    }

    private function primaryCount(): int
    {
        return Property::where('customer_id', $this->customer->id)->where('is_primary', true)->count();
    }
}
