<?php

namespace App\Services\Llm;

interface LlmClient
{
    /** Ask the model to return a repaired JSON document, or null when it cannot. */
    public function repair(string $rawText, string $parseError): ?string;
}
