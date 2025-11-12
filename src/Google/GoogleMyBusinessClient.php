<?php

namespace App\Google;

use RuntimeException;

class GoogleMyBusinessClient
{
    private string $accessToken;

    public function __construct(string $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listReviews(string $locationName): array
    {
        $query = http_build_query([
            'orderBy' => 'updateTime desc',
            'pageSize' => 100,
        ], '', '&', PHP_QUERY_RFC3986);

        $url = sprintf(
            'https://mybusiness.googleapis.com/v4/%s/reviews?%s',
            $this->encodePath($locationName),
            $query
        );
        $response = $this->request('GET', $url);

        return $response['reviews'] ?? [];
    }

    public function replyToReview(string $reviewName, string $comment): void
    {
        $url = sprintf('https://mybusiness.googleapis.com/v4/%s:reply', $this->encodePath($reviewName));
        $this->request('POST', $url, [
            'comment' => $comment,
        ]);
    }

    /**
     * @param array<string, mixed>|null $body
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $url, ?array $body = null): array
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new RuntimeException('Unable to initialise cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array_filter([
                'Authorization: Bearer ' . $this->accessToken,
                $body !== null ? 'Content-Type: application/json' : null,
            ]),
        ]);

        if ($body !== null) {
            $encodedBody = json_encode($body, JSON_THROW_ON_ERROR);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedBody);
        }

        $result = curl_exec($ch);
        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException(sprintf('Google API request failed: %s', $error));
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode >= 400) {
            throw new RuntimeException(sprintf('Google API request returned status %d: %s', $statusCode, $result));
        }

        if ($result === '') {
            return [];
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($result, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Invalid response from Google API.');
        }

        return $decoded;
    }

    private function encodePath(string $path): string
    {
        $segments = array_map('rawurlencode', explode('/', $path));
        return implode('/', $segments);
    }
}
