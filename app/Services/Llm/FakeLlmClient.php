<?php

namespace App\Services\Llm;

class FakeLlmClient implements LlmClient
{
    public ?string $response = null;

    /** @var list<array{raw_text: string, parse_error: string}> */
    public array $calls = [];

    public function repair(string $rawText, string $parseError): ?string
    {
        $this->calls[] = ['raw_text' => $rawText, 'parse_error' => $parseError];

        return $this->response;
    }
}
