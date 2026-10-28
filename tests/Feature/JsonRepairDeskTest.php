<?php

use App\Models\RepairedJson;
use App\Models\RepairJob;
use App\Services\Json\JsonRepairer;
use App\Services\Llm\FakeLlmClient;
use App\Services\Llm\LlmClient;

test('repairs a trailing comma before validating the Harbor Desk event schema', function (): void {
    $result = app(JsonRepairer::class)->repair(<<<'JSON'
{
  "event_type": "order.dispatched",
  "order_id": "HD-10027",
}
JSON);

    expect($result['valid'])->toBeTrue()
        ->and($result['repair_source'])->toBe('deterministic')
        ->and($result['json'])->toContain('"order_id": "HD-10027"')
        ->and($result['changes'])->toContain('Removed trailing commas');
});

test('keeps valid JSON invalid when event_type is missing', function (): void {
    $result = app(JsonRepairer::class)->repair('{"order_id":"HD-10027"}');

    expect($result['valid'])->toBeFalse()
        ->and($result['json'])->not->toBeNull()
        ->and($result['errors'])->toContain('event_type is required and must be a non-empty string.');
});

test('uses the fake llm binding when no Gemini key is configured', function (): void {
    config()->set('services.gemini.key', null);
    app()->forgetInstance(LlmClient::class);

    expect(app(LlmClient::class))->toBeInstanceOf(FakeLlmClient::class);
});

test('uses the fake llm only after deterministic parsing fails and validates its output', function (): void {
    $fake = new FakeLlmClient;
    $fake->response = '{"event_type":"order.created","order_id":"HD-52016"}';
    $repairer = new JsonRepairer($fake);

    $result = $repairer->repair('{not valid json');

    expect($result['valid'])->toBeTrue()
        ->and($result['llm_used'])->toBeTrue()
        ->and($result['repair_source'])->toBe('gemini')
        ->and($fake->calls)->toHaveCount(1);

    $fake->response = '{"order_id":"HD-52016"}';
    $schemaResult = $repairer->repair('{not valid json');

    expect($schemaResult['valid'])->toBeFalse()
        ->and($schemaResult['errors'])->toContain('event_type is required and must be a non-empty string.');
});

test('guest can submit a repair and receive a persisted result', function (): void {
    $response = $this->postJson('/repair', [
        'raw_text' => "{'event_type':'order.created','order_id':'HD-24018',}",
    ]);

    $response->assertOk()
        ->assertJsonPath('valid', true)
        ->assertJsonPath('repair_source', 'deterministic');

    expect(RepairedJson::query()->first())
        ->not->toBeNull()
        ->and(RepairedJson::query()->first()?->valid)->toBeTrue();
});

test('database seeder creates the three demonstration repair jobs', function (): void {
    $this->seed();

    expect(RepairJob::query()->count())->toBe(3)
        ->and(RepairedJson::query()->count())->toBe(3);
});

test('single quotes are converted without damaging apostrophes in values', function (): void {
    $result = app(JsonRepairer::class)->repair("{'event_type':'order.note','order_id':'HD-80012','note':'It\\'s ready'}");

    expect($result['valid'])->toBeTrue()
        ->and($result['json'])->toContain("It's ready");
});
