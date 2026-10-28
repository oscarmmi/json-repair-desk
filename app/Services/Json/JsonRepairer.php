<?php

namespace App\Services\Json;

use App\Services\Llm\LlmClient;
use JsonException;
use Throwable;

class JsonRepairer
{
    public function __construct(private readonly LlmClient $llmClient) {}

    /**
     * @return array{
     *     json: string|null,
     *     valid: bool,
     *     errors: list<string>,
     *     repair_source: string,
     *     llm_used: bool,
     *     changes: list<string>
     * }
     */
    public function repair(string $rawText): array
    {
        $deterministic = $rawText;
        $changes = [];

        $withQuotedStrings = $this->convertSingleQuotedStrings($deterministic);
        if ($withQuotedStrings !== $deterministic) {
            $deterministic = $withQuotedStrings;
            $changes[] = 'Converted single-quoted strings to JSON strings';
        }

        $withoutTrailingCommas = $this->removeTrailingCommas($deterministic);
        if ($withoutTrailingCommas !== $deterministic) {
            $deterministic = $withoutTrailingCommas;
            $changes[] = 'Removed trailing commas';
        }

        $candidate = $deterministic;
        $source = $changes === [] ? 'none' : 'deterministic';
        $llmUsed = false;
        $parseError = $this->parseError($candidate);

        if ($parseError !== null) {
            try {
                $llmCandidate = $this->llmClient->repair($rawText, $parseError);
                if (is_string($llmCandidate) && $llmCandidate !== '' && $this->parseError($llmCandidate) === null) {
                    $candidate = $llmCandidate;
                    $source = 'gemini';
                    $llmUsed = true;
                }
            } catch (Throwable) {
                // Optional model failures must never prevent deterministic results from being shown.
            }
        }

        $errors = [];
        $json = null;
        $decoded = null;
        try {
            $decoded = json_decode($candidate, false, 512, JSON_THROW_ON_ERROR);
            $json = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $errors[] = 'JSON syntax error: '.$exception->getMessage();
        }

        if ($json !== null) {
            if (! is_object($decoded)) {
                $errors[] = 'The payload must be a JSON object.';
            } else {
                foreach (['event_type', 'order_id'] as $field) {
                    $value = $decoded->{$field} ?? null;
                    if (! is_string($value) || trim($value) === '') {
                        $errors[] = "{$field} is required and must be a non-empty string.";
                    }
                }
            }
        }

        return [
            'json' => $json,
            'valid' => $json !== null && $errors === [],
            'errors' => $errors,
            'repair_source' => $source,
            'llm_used' => $llmUsed,
            'changes' => $changes,
        ];
    }

    private function parseError(string $value): ?string
    {
        try {
            json_decode($value, false, 512, JSON_THROW_ON_ERROR);

            return null;
        } catch (JsonException $exception) {
            return $exception->getMessage();
        }
    }

    private function convertSingleQuotedStrings(string $input): string
    {
        $output = '';
        $length = strlen($input);
        $insideDoubleQuotedString = false;

        for ($index = 0; $index < $length; $index++) {
            $character = $input[$index];

            if ($insideDoubleQuotedString) {
                $output .= $character;
                if ($character === '\\' && $index + 1 < $length) {
                    $output .= $input[++$index];
                } elseif ($character === '"') {
                    $insideDoubleQuotedString = false;
                }

                continue;
            }

            if ($character === '"') {
                $insideDoubleQuotedString = true;
                $output .= $character;

                continue;
            }

            if ($character !== "'") {
                $output .= $character;

                continue;
            }

            $value = '';
            $closed = false;
            for ($index++; $index < $length; $index++) {
                $part = $input[$index];
                if ($part === "'") {
                    $closed = true;
                    break;
                }

                if ($part === '\\' && $index + 1 < $length) {
                    $escaped = $input[++$index];
                    if ($escaped === "'") {
                        $value .= "'";
                    } elseif ($escaped === '"') {
                        $value .= '\\"';
                    } elseif (str_contains('\\/bfnrt', $escaped)) {
                        $value .= '\\'.$escaped;
                    } elseif ($escaped === 'u' && preg_match('/^[0-9a-fA-F]{4}$/', substr($input, $index + 1, 4)) === 1) {
                        $value .= '\\u'.substr($input, $index + 1, 4);
                        $index += 4;
                    } else {
                        $value .= '\\\\'.$escaped;
                    }

                    continue;
                }

                $value .= $part === '"' ? '\\"' : $part;
            }

            if (! $closed) {
                return $output."'".$value;
            }

            $output .= '"'.$value.'"';
        }

        return $output;
    }

    private function removeTrailingCommas(string $input): string
    {
        $output = '';
        $length = strlen($input);
        $insideString = false;

        for ($index = 0; $index < $length; $index++) {
            $character = $input[$index];

            if ($insideString) {
                $output .= $character;
                if ($character === '\\' && $index + 1 < $length) {
                    $output .= $input[++$index];
                } elseif ($character === '"') {
                    $insideString = false;
                }

                continue;
            }

            if ($character === '"') {
                $insideString = true;
                $output .= $character;

                continue;
            }

            if ($character === ',') {
                $next = $index + 1;
                while ($next < $length && ctype_space($input[$next])) {
                    $next++;
                }
                if ($next < $length && ($input[$next] === '}' || $input[$next] === ']')) {
                    continue;
                }
            }

            $output .= $character;
        }

        return $output;
    }
}
