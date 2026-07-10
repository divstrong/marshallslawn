<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\Translation;
use App\Services\Translation\TranslationDriver;
use App\Services\Translation\TranslationService;
use App\Http\Resources\ChatMessageResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/** Records batch calls; returns "ES:<text>" so translations are recognizable. */
class FakeTranslationDriver implements TranslationDriver
{
    public int $calls = 0;

    /** @var array<int, int> */
    public array $batchSizes = [];

    public function translateBatch(array $texts, string $source, string $target): array
    {
        $this->calls++;
        $this->batchSizes[] = count($texts);

        return array_map(fn ($t) => strtoupper($target) . ':' . $t, $texts);
    }
}

class TranslationServiceTest extends TestCase
{
    use RefreshDatabase;

    private FakeTranslationDriver $driver;

    private TranslationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = new FakeTranslationDriver();
        $this->app->instance(TranslationDriver::class, $this->driver);
        $this->service = $this->app->make(TranslationService::class);
    }

    public function test_english_target_is_never_translated(): void
    {
        $this->assertFalse($this->service->isTranslatable('en'));
        $this->assertSame('Mow the lawn', $this->service->translate('Mow the lawn', 'en'));
        $this->assertSame(0, $this->driver->calls);
    }

    public function test_it_translates_and_caches(): void
    {
        $first = $this->service->translate('Mow the lawn', 'es');
        $this->assertSame('ES:Mow the lawn', $first);
        $this->assertSame(1, $this->driver->calls);

        $this->assertDatabaseHas('translations', [
            'target_locale' => 'es',
            'source_text' => 'Mow the lawn',
            'translated_text' => 'ES:Mow the lawn',
        ]);

        // Second call is served from cache — the driver is not asked again.
        $second = $this->service->translate('Mow the lawn', 'es');
        $this->assertSame('ES:Mow the lawn', $second);
        $this->assertSame(1, $this->driver->calls);
    }

    public function test_translate_many_dedupes_and_batches_only_the_misses(): void
    {
        Translation::create([
            'hash' => Translation::hashFor('en', 'es', 'Cached'),
            'source_locale' => 'en',
            'target_locale' => 'es',
            'source_text' => 'Cached',
            'translated_text' => 'ES:Cached',
        ]);

        $out = $this->service->translateMany(['Cached', 'Fresh', 'Fresh', 'Another'], 'es');

        $this->assertSame('ES:Cached', $out['Cached']);
        $this->assertSame('ES:Fresh', $out['Fresh']);
        $this->assertSame('ES:Another', $out['Another']);

        // One batched call carrying only the two unique misses.
        $this->assertSame(1, $this->driver->calls);
        $this->assertSame([2], $this->driver->batchSizes);
    }

    public function test_a_driver_miss_falls_back_to_the_original_text(): void
    {
        $missing = new class implements TranslationDriver {
            public function translateBatch(array $texts, string $source, string $target): array
            {
                return array_map(fn () => null, $texts);
            }
        };
        $this->app->instance(TranslationDriver::class, $missing);
        $service = $this->app->make(TranslationService::class);

        $this->assertSame('Keep me', $service->translate('Keep me', 'es'));
        $this->assertDatabaseCount('translations', 0);
    }

    public function test_chat_resource_translates_office_messages_but_not_foreman_ones(): void
    {
        $office = ChatMessage::make(['sender' => ChatMessage::SENDER_OFFICE, 'body' => 'Please start']);
        $foreman = ChatMessage::make(['sender' => ChatMessage::SENDER_FOREMAN, 'body' => 'On my way']);

        $request = Request::create('/api/chat', 'GET');
        $request->headers->set('X-App-Language', 'es');

        $officeOut = (new ChatMessageResource($office))->toArray($request);
        $foremanOut = (new ChatMessageResource($foreman))->toArray($request);

        $this->assertSame('ES:Please start', $officeOut['body']);
        $this->assertSame('On my way', $foremanOut['body'], 'the foreman\'s own message is not translated');
    }

    public function test_resource_leaves_english_requests_untouched(): void
    {
        $office = ChatMessage::make(['sender' => ChatMessage::SENDER_OFFICE, 'body' => 'Please start']);

        $request = Request::create('/api/chat', 'GET');
        $request->headers->set('X-App-Language', 'en');

        $out = (new ChatMessageResource($office))->toArray($request);

        $this->assertSame('Please start', $out['body']);
        $this->assertSame(0, $this->driver->calls);
    }
}
