<?php

namespace App\Support;

class DemoPayloads
{
    /** @return list<array{id: string, title: string, description: string, raw_text: string}> */
    public static function all(): array
    {
        return [
            [
                'id' => 'trailing-comma',
                'title' => 'Trailing comma',
                'description' => 'Order dispatched · trailing comma after the final field',
                'raw_text' => "{\n  \"event_type\": \"order.dispatched\",\n  \"order_id\": \"HD-48291\",\n  \"carrier\": \"Northstar\",\n}",
            ],
            [
                'id' => 'single-quotes',
                'title' => 'Single quotes',
                'description' => 'Delivery exception · JavaScript-style quotes',
                'raw_text' => "{\n  'event_type': 'delivery.exception',\n  'order_id': 'HD-73014',\n  'reason': 'Address not found'\n}",
            ],
            [
                'id' => 'missing-event',
                'title' => 'Missing event_type',
                'description' => 'Return received · valid JSON, incomplete schema',
                'raw_text' => "{\n  \"order_id\": \"HD-91506\",\n  \"warehouse\": \"East Dock\"\n}",
            ],
        ];
    }
}
