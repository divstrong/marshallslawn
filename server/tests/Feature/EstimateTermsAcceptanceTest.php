<?php

namespace Tests\Feature;

use App\Livewire\SettingsTerms;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimateTermsAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private function estimate(): Estimate
    {
        $customer = Customer::create(['first_name' => 'Ada', 'last_name' => 'Byrne', 'status' => 'active']);

        return Estimate::create([
            'customer_id' => $customer->id,
            'estimate_number' => 'EST-500',
            'share_token' => 'abc123',
            'status' => 'sent',
            'subtotal' => 200,
            'total' => 200,
        ]);
    }

    public function test_the_public_estimate_shows_the_terms_from_settings(): void
    {
        Setting::updateOrCreate(
            ['key' => SettingsTerms::SETTING_KEY],
            ['value' => 'Mow twice monthly, cancel with 24 hours notice.', 'group' => 'terms'],
        );

        $this->get('/estimate/' . $this->estimate()->share_token)
            ->assertOk()
            ->assertSee('Mow twice monthly, cancel with 24 hours notice.')
            ->assertSee('I agree to the Terms &amp; Conditions', false)
            ->assertSee('name="terms_accepted"', false);
    }

    public function test_the_default_terms_show_when_none_are_saved(): void
    {
        $this->get('/estimate/' . $this->estimate()->share_token)
            ->assertOk()
            ->assertSee('By accepting this estimate, you authorize');
    }

    public function test_accepting_without_agreeing_to_the_terms_is_rejected(): void
    {
        $estimate = $this->estimate();

        $this->from('/estimate/' . $estimate->share_token)
            ->post('/estimate/' . $estimate->share_token . '/accept', ['accepted_items' => [1]])
            ->assertSessionHasErrors('terms_accepted');

        $this->assertSame('sent', $estimate->fresh()->status, 'the estimate must not be accepted');
        $this->assertNull($estimate->fresh()->accepted_at);
    }

    public function test_accepting_with_agreement_records_the_terms_in_force(): void
    {
        Setting::updateOrCreate(
            ['key' => SettingsTerms::SETTING_KEY],
            ['value' => 'Version one of the terms.', 'group' => 'terms'],
        );
        $estimate = $this->estimate();

        $this->post('/estimate/' . $estimate->share_token . '/accept', [
            'terms_accepted' => '1',
            'accepted_items' => [1, 2],
        ])->assertSessionHasNoErrors();

        $estimate->refresh();
        $this->assertSame('accepted', $estimate->status);
        $this->assertNotNull($estimate->accepted_at);
        $this->assertNotNull($estimate->terms_accepted_at);
        $this->assertSame('Version one of the terms.', $estimate->accepted_terms);

        // Editing the terms afterwards must not rewrite what was agreed to.
        Setting::updateOrCreate(
            ['key' => SettingsTerms::SETTING_KEY],
            ['value' => 'Version two of the terms.', 'group' => 'terms'],
        );
        $this->assertSame('Version one of the terms.', $estimate->fresh()->accepted_terms);
    }
}
