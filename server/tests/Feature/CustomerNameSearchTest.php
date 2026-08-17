<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Customer lookup has to span first and last name.
 *
 * The previous implementation matched the whole search term against each column
 * separately, so "Laura M" could never match Laura Marshall — no single column
 * contains that string.
 */
class CustomerNameSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->make('Laura', 'Marshall', 'laura@example.com');
        $this->make('Marcus', 'Webb', 'marcus@example.com');
        $this->make('Laura', 'Nguyen', 'ln@example.com');
    }

    private function make(string $first, string $last, string $email): Customer
    {
        return Customer::create([
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'status' => 'active',
        ]);
    }

    private function search(string $term): array
    {
        return Customer::query()->searchName($term)->pluck('last_name')->all();
    }

    public function test_a_partial_first_and_last_name_finds_the_customer(): void
    {
        // The reported case.
        $this->assertSame(['Marshall'], $this->search('Laura M'));
    }

    public function test_the_full_name_finds_the_customer(): void
    {
        $this->assertSame(['Marshall'], $this->search('Laura Marshall'));
    }

    public function test_last_name_alone_finds_the_customer(): void
    {
        $this->assertSame(['Marshall'], $this->search('Marshall'));
    }

    public function test_first_name_alone_matches_everyone_sharing_it(): void
    {
        $found = $this->search('Laura');

        sort($found);
        $this->assertSame(['Marshall', 'Nguyen'], $found);
    }

    public function test_search_is_order_independent(): void
    {
        $this->assertSame(['Marshall'], $this->search('Marshall Laura'));
    }

    public function test_a_term_matching_nobody_returns_nothing(): void
    {
        $this->assertSame([], $this->search('Laura Webb'));
    }

    public function test_an_empty_term_does_not_filter(): void
    {
        $this->assertCount(3, $this->search('   '));
    }

    public function test_company_name_and_email_are_still_searchable(): void
    {
        $this->make('Dana', 'Reyes', 'ops@greenacres.test')
            ->update(['company_name' => 'Green Acres HOA']);

        $this->assertSame(['Reyes'], $this->search('Green Acres'));
        $this->assertSame(['Reyes'], $this->search('greenacres.test'));
    }
}
