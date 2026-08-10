<?php

namespace Tests\Feature;

use App\Filament\Resources\CustomerResource\Pages\ViewCustomer;
use App\Filament\Resources\PropertyResource\Pages\EditProperty;
use App\Filament\Resources\PropertyResource\Pages\ListProperties;
use App\Models\Customer;
use App\Models\Property;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PropertyPrimaryImageTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $this->customer = Customer::create(['first_name' => 'Dana', 'last_name' => 'Whitlock', 'status' => 'active']);
    }

    public function test_an_uploaded_primary_image_is_stored_and_saved_to_the_property(): void
    {
        $property = Property::create(['customer_id' => $this->customer->id, 'address' => '31 Holly Way']);

        Livewire::test(EditProperty::class, ['record' => $property->id])
            ->fillForm(['primary_image_path' => UploadedFile::fake()->image('house.jpg')])
            ->call('save')
            ->assertHasNoFormErrors();

        $path = $property->fresh()->primary_image_path;
        $this->assertNotNull($path, 'the upload is persisted to the property');
        $this->assertStringStartsWith('property-images/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_the_image_url_is_null_without_an_upload_and_set_with_one(): void
    {
        $property = Property::create(['customer_id' => $this->customer->id, 'address' => '4 Ash Ct']);
        $this->assertNull($property->primaryImageUrl(), 'no photo means no URL, so callers fall back');

        $property->update(['primary_image_path' => 'property-images/house.jpg']);
        $this->assertStringContainsString('property-images/house.jpg', $property->fresh()->primaryImageUrl());

        $this->assertStringContainsString('property-placeholder.svg', Property::placeholderImageUrl());
    }

    public function test_the_properties_table_renders_the_photo_column(): void
    {
        $property = Property::create([
            'customer_id' => $this->customer->id,
            'address' => '31 Holly Way',
            'primary_image_path' => 'property-images/house.jpg',
        ]);

        Livewire::test(ListProperties::class)
            ->assertCanSeeTableRecords([$property])
            ->assertTableColumnExists('primary_image_path')
            ->assertOk();
    }

    public function test_the_customer_overview_shows_the_primary_propertys_photo(): void
    {
        Property::create([
            'customer_id' => $this->customer->id,
            'address' => '31 Holly Way',
            'is_primary' => true,
            'primary_image_path' => 'property-images/house.jpg',
        ]);

        Livewire::test(ViewCustomer::class, ['record' => $this->customer->id])
            ->assertOk()
            ->assertSee('property-images/house.jpg')
            ->assertDontSee('property-placeholder.svg');
    }

    public function test_the_customer_overview_falls_back_to_the_placeholder(): void
    {
        // Primary without a photo gets the placeholder; the secondary gets no
        // thumbnail at all, so the card stays compact.
        Property::create(['customer_id' => $this->customer->id, 'address' => '31 Holly Way', 'is_primary' => true]);
        Property::create(['customer_id' => $this->customer->id, 'address' => '9 Back Lot']);

        Livewire::test(ViewCustomer::class, ['record' => $this->customer->id])
            ->assertOk()
            ->assertSee('property-placeholder.svg')
            ->assertSee('No photo on file yet');
    }
}
