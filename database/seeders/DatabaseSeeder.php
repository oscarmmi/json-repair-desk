<?php

namespace Database\Seeders;

use App\Models\RepairJob;
use App\Support\DemoPayloads;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $expectedResults = [
            ['valid' => true, 'json' => "{\n    \"event_type\": \"order.dispatched\",\n    \"order_id\": \"HD-48291\",\n    \"carrier\": \"Northstar\"\n}", 'errors' => []],
            ['valid' => true, 'json' => "{\n    \"event_type\": \"delivery.exception\",\n    \"order_id\": \"HD-73014\",\n    \"reason\": \"Address not found\"\n}", 'errors' => []],
            ['valid' => false, 'json' => "{\n    \"order_id\": \"HD-91506\",\n    \"warehouse\": \"East Dock\"\n}", 'errors' => ['event_type is required and must be a non-empty string.']],
        ];

        foreach (DemoPayloads::all() as $index => $payload) {
            $expected = $expectedResults[$index];
            $job = RepairJob::firstOrCreate(
                ['raw_text' => $payload['raw_text']],
                ['status' => $expected['valid'] ? 'valid' : 'invalid'],
            );

            $job->repairedJson()->updateOrCreate([], [
                'json' => $expected['json'],
                'valid' => $expected['valid'],
                'errors' => $expected['errors'],
            ]);
        }
    }
}
