<?php

namespace App\Google;

use RuntimeException;

class GoogleMyBusinessClient
{
    private string $accessToken;
    /** @var array<string, string> */
    private array $locationCache = [];
    /** @var array<int, array<string, mixed>>|null */
    private ?array $cachedAccounts = null;
    /** @var array<string, array<int, array<string, mixed>>> */
    private array $locationsCache = [];
    private ?string $lastResolvedLocationName = null;

    public function __construct(string $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listReviews(string $locationName): array
    {
        $resolvedLocation = $this->resolveLocationName($locationName);
        $reviews = [];
        $pageToken = null;

        do {
            $query = http_build_query(array_filter([
                'orderBy' => 'updateTime desc',
                'pageSize' => 100,
                'pageToken' => $pageToken,
            ]), '', '&', PHP_QUERY_RFC3986);

            $url = sprintf(
                'https://mybusiness.googleapis.com/v4/%s/reviews?%s',
                $this->encodePath($resolvedLocation),
                $query
            );

            try {
                $response = $this->request('GET', $url);
            } catch (RuntimeException $exception) {
                if (str_contains($exception->getMessage(), 'status 404')) {
                    throw new RuntimeException('Google konum kimliği bulunamadı. Business Profile hesabında görünen accounts/.../locations/... formatındaki değerle eşleşen bir kayıt bulunamadı.');
                }

                throw $exception;
            }

            $pageReviews = $response['reviews'] ?? [];
            if (is_array($pageReviews) && $pageReviews !== []) {
                $reviews = array_merge($reviews, $pageReviews);
            }

            $pageToken = isset($response['nextPageToken']) ? (string)$response['nextPageToken'] : null;
        } while ($pageToken);

        return $reviews;
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

    private function resolveLocationName(string $locationIdentifier): string
    {
        $identifier = trim($locationIdentifier);

        if ($identifier === '') {
            throw new RuntimeException('Google konum kimliği boş olamaz.');
        }

        $cacheKey = strtolower($identifier);
        if (isset($this->locationCache[$identifier])) {
            return $this->setLastResolvedLocation($this->locationCache[$identifier]);
        }

        if (isset($this->locationCache[$cacheKey])) {
            return $this->setLastResolvedLocation($this->locationCache[$cacheKey]);
        }

        $candidate = $this->extractLocationCandidate($identifier);
        $candidateKey = strtolower($candidate);

        if (isset($this->locationCache[$candidate])) {
            return $this->setLastResolvedLocation($this->locationCache[$candidate]);
        }

        if (isset($this->locationCache[$candidateKey])) {
            return $this->setLastResolvedLocation($this->locationCache[$candidateKey]);
        }

        $potentialResources = array_values(array_unique(array_filter([
            $identifier,
            $candidate,
        ])));

        $accounts = $this->listAccounts();
        if (!$accounts) {
            throw new RuntimeException('Google Business Profile hesabı bulunamadı. OAuth izinlerini kontrol edin.');
        }

        foreach ($accounts as $account) {
            $accountName = $account['name'] ?? null;
            if (!$accountName) {
                continue;
            }

            $locations = $this->listLocationsForAccount($accountName);

            foreach ($locations as $location) {
                $name = $location['name'] ?? null;
                if (!$name) {
                    continue;
                }

                $locationId = (string)preg_replace('#^.*/locations/#', '', $name);
                $placeId = $location['metadata']['placeId'] ?? null;
                $matches = array_filter([
                    $name,
                    strtolower($name),
                    $locationId,
                    strtolower($locationId),
                    $placeId,
                    is_string($placeId) ? strtolower($placeId) : null,
                ]);

                foreach ($potentialResources as $resource) {
                    $resourceLower = strtolower($resource);
                    if (in_array($resource, $matches, true) || in_array($resourceLower, $matches, true)) {
                        $this->rememberLocationMapping($identifier, $name, $locationId, $placeId);
                        if ($candidate !== $identifier) {
                            $this->rememberLocationMapping($candidate, $name, $locationId, $placeId);
                        }

                        return $this->setLastResolvedLocation($name);
                    }
                }
            }
        }

        throw new RuntimeException('Google konum kimliği doğrulanamadı. Business Profile API\'de görünen accounts/.../locations/... formatındaki tam kaynak adını girin.');
    }

    private function extractLocationCandidate(string $input): string
    {
        $trimmed = trim($input);

        if (preg_match('#(accounts/[^\s/]+/locations/[^\s/?#]+)#i', $trimmed, $matches)) {
            return $matches[1];
        }

        if (preg_match('#(locations/[^\s/?#]+)#i', $trimmed, $matches)) {
            return $matches[1];
        }

        if (preg_match('#ChI[A-Za-z0-9_-]+#', $trimmed, $matches)) {
            return $matches[0];
        }

        if (preg_match('#\d{6,}#', $trimmed, $matches)) {
            return $matches[0];
        }

        return $trimmed;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listAccounts(): array
    {
        if ($this->cachedAccounts !== null) {
            return $this->cachedAccounts;
        }

        $response = $this->request('GET', 'https://mybusiness.googleapis.com/v4/accounts');
        $accounts = $response['accounts'] ?? [];

        if (!is_array($accounts)) {
            $accounts = [];
        }

        $this->cachedAccounts = $accounts;

        return $this->cachedAccounts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listLocationsForAccount(string $accountName): array
    {
        if (isset($this->locationsCache[$accountName])) {
            return $this->locationsCache[$accountName];
        }

        $locations = [];
        $pageToken = null;

        do {
            $query = http_build_query(array_filter([
                'pageSize' => 100,
                'readMask' => 'name,title,storeCode,metadata',
                'pageToken' => $pageToken,
            ]), '', '&', PHP_QUERY_RFC3986);

            $url = sprintf('https://mybusiness.googleapis.com/v4/%s/locations?%s', $this->encodePath($accountName), $query);
            $response = $this->request('GET', $url);

            $pageLocations = $response['locations'] ?? [];
            if (is_array($pageLocations)) {
                $locations = array_merge($locations, $pageLocations);
            }

            $pageToken = isset($response['nextPageToken']) ? (string)$response['nextPageToken'] : null;
        } while ($pageToken);

        $this->locationsCache[$accountName] = $locations;

        return $locations;
    }

    private function rememberLocationMapping(string $input, string $canonicalName, ?string $locationId = null, ?string $placeId = null): void
    {
        $this->locationCache[$input] = $canonicalName;
        $this->locationCache[strtolower($input)] = $canonicalName;
        $this->locationCache[$canonicalName] = $canonicalName;
        $this->locationCache[strtolower($canonicalName)] = $canonicalName;

        if ($locationId) {
            $this->locationCache[$locationId] = $canonicalName;
            $this->locationCache[strtolower($locationId)] = $canonicalName;
        }

        if ($placeId) {
            $this->locationCache[$placeId] = $canonicalName;
            $this->locationCache[strtolower($placeId)] = $canonicalName;
        }
    }

    private function setLastResolvedLocation(string $locationName): string
    {
        $this->lastResolvedLocationName = $locationName;

        return $locationName;
    }

    public function getLastResolvedLocationName(): ?string
    {
        return $this->lastResolvedLocationName;
    }
}
