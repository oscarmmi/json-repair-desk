<?php

namespace App\Services\Llm;

use Illuminate\Support\Facades\Http;
use Throwable;

class GeminiLlmClient implements LlmClient
{
    public function repair(string $rawText, string $parseError): ?string
    {
        $apiKey = (string) config('services.gemini.key');

        if ($apiKey === '') {
            return null;
        }

        try {
            $model = (string) config('services.gemini.model', 'gemini-2.5-flash');
            $response = Http::acceptJson()
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->connectTimeout(4)
                ->timeout(12)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                    'systemInstruction' => [
                        'parts' => [[
                            'text' => 'Repair syntax only. Return exactly one valid JSON document with the same data and values. Do not invent fields or values. Do not use markdown or explanations.',
                        ]],
                    ],
                    'contents' => [[
                        'role' => 'user',
                        'parts' => [[
                            'text' => "Parser error: {$parseError}\n\nJSON to repair:\n{$rawText}",
                        ]],
                    ]],
                    'generationConfig' => ['temperature' => 0],
                ]);

            if (! $response->successful()) {
                return null;
            }

            $text = $response->json('candidates.0.content.parts.0.text');

            if (! is_string($text)) {
                return null;
            }

            return trim(preg_replace('/\A```(?:json)?\s*|\s*```\z/i', '', $text) ?? $text);
        } catch (Throwable) {
            return null;
        }
    }
}
