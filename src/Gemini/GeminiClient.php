<?php

namespace App\Gemini;

use RuntimeException;

class GeminiClient
{
    private string $apiKey;
    private string $model;

    public function __construct(string $apiKey, string $model = 'gemini-2.5-flash-lite-preview-09-2025')
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function generateReply(string $prompt): string
    {
        $url = sprintf('https://generativelanguage.googleapis.com/v1beta/%s:generateContent?key=%s', $this->model, $this->apiKey);
        $payload = [
            'contents' => [[
                'parts' => [[
                    'text' => $prompt,
                ]],
            ]],
        ];

        $ch = curl_init();
        if ($ch === false) {
            throw new RuntimeException('Unable to initialise cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
        ]);

        $result = curl_exec($ch);
        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException(sprintf('Gemini API request failed: %s', $error));
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode >= 400) {
            throw new RuntimeException(sprintf('Gemini API request returned status %d: %s', $statusCode, $result));
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($result, true);
        if (!isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
            throw new RuntimeException('Gemini API did not return a valid response.');
        }

        return trim($decoded['candidates'][0]['content']['parts'][0]['text']);
    }
}
