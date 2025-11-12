<?php

namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

class BusinessRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM businesses ORDER BY name');
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        return array_map([$this, 'mapRow'], $rows ?: []);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM businesses WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        return $row ? $this->mapRow($row) : null;
    }

    /**
     * @param array<string, string|null> $data
     */
    public function create(array $data): int
    {
        if (empty($data['name']) || empty($data['google_location']) || empty($data['google_client_id']) || empty($data['google_client_secret']) || empty($data['google_oauth_redirect_uri']) || empty($data['google_oauth_javascript_origin']) || empty($data['gemini_api_key'])) {
            throw new RuntimeException('Name, Google location, Google OAuth client credentials, redirect URI, JavaScript origin and Gemini API key are required.');
        }

        $sql = 'INSERT INTO businesses (
                    name,
                    google_location,
                    google_client_id,
                    google_client_secret,
                    google_oauth_redirect_uri,
                    google_oauth_javascript_origin,
                    google_access_token,
                    google_refresh_token,
                    google_access_token_expires_at,
                    gemini_api_key,
                    gemini_model,
                    connection_status,
                    connection_message,
                    connection_checked_at,
                    created_at,
                    updated_at
                )
                VALUES (
                    :name,
                    :google_location,
                    :google_client_id,
                    :google_client_secret,
                    :google_oauth_redirect_uri,
                    :google_oauth_javascript_origin,
                    :google_access_token,
                    :google_refresh_token,
                    :google_access_token_expires_at,
                    :gemini_api_key,
                    :gemini_model,
                    :connection_status,
                    :connection_message,
                    :connection_checked_at,
                    NOW(),
                    NOW()
                )';

        $stmt = $this->pdo->prepare($sql);

        $params = [
            'name' => $data['name'],
            'google_location' => $data['google_location'],
            'google_client_id' => $data['google_client_id'],
            'google_client_secret' => $data['google_client_secret'],
            'google_oauth_redirect_uri' => $data['google_oauth_redirect_uri'],
            'google_oauth_javascript_origin' => $data['google_oauth_javascript_origin'],
            'google_access_token' => $data['google_access_token'] ?? null,
            'google_refresh_token' => $data['google_refresh_token'] ?? null,
            'google_access_token_expires_at' => $data['google_access_token_expires_at'] ?? null,
            'gemini_api_key' => $data['gemini_api_key'],
            'gemini_model' => $data['gemini_model'] ?? 'gemini-2.5-flash-lite-preview-09-2025',
            'connection_status' => $data['connection_status'] ?? 'pending',
            'connection_message' => $data['connection_message'] ?? null,
            'connection_checked_at' => $data['connection_checked_at'] ?? null,
        ];

        try {
            $stmt->execute($params);
        } catch (PDOException $exception) {
            throw new RuntimeException('Failed to create business: ' . $exception->getMessage(), 0, $exception);
        }

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * @param array<string, string|null> $row
     * @return array<string, mixed>
     */
    private function mapRow(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'googleLocation' => (string)$row['google_location'],
            'googleClientId' => (string)$row['google_client_id'],
            'googleClientSecret' => (string)$row['google_client_secret'],
            'googleRedirectUri' => (string)$row['google_oauth_redirect_uri'],
            'googleJavascriptOrigin' => (string)$row['google_oauth_javascript_origin'],
            'googleAccessToken' => $row['google_access_token'] ?? null,
            'googleRefreshToken' => $row['google_refresh_token'] ?? null,
            'googleAccessTokenExpiresAt' => $row['google_access_token_expires_at'] ?? null,
            'geminiApiKey' => (string)$row['gemini_api_key'],
            'geminiModel' => $row['gemini_model'] ?: 'gemini-2.5-flash-lite-preview-09-2025',
            'connectionStatus' => $row['connection_status'] ?? 'never',
            'connectionMessage' => $row['connection_message'] ?? null,
            'connectionCheckedAt' => $row['connection_checked_at'] ?? null,
            'lastCheckedAt' => $row['last_checked_at'] ?? null,
            'lastCheckFetched' => isset($row['last_check_fetched']) ? (int)$row['last_check_fetched'] : 0,
            'lastCheckReplied' => isset($row['last_check_replied']) ? (int)$row['last_check_replied'] : 0,
            'createdAt' => $row['created_at'] ?? null,
            'updatedAt' => $row['updated_at'] ?? null,
        ];
    }

    public function updateTokens(int $businessId, string $accessToken, ?string $refreshToken = null, ?string $expiresAt = null): void
    {
        $sql = 'UPDATE businesses
                SET google_access_token = :access_token,
                    google_access_token_expires_at = :expires_at,
                    updated_at = NOW()' . ($refreshToken !== null ? ', google_refresh_token = :refresh_token' : '') . '
                WHERE id = :id';

        $params = [
            'access_token' => $accessToken,
            'expires_at' => $expiresAt,
            'id' => $businessId,
        ];

        if ($refreshToken !== null) {
            $params['refresh_token'] = $refreshToken;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function updateGoogleLocation(int $businessId, string $locationName): void
    {
        $stmt = $this->pdo->prepare('UPDATE businesses
                SET google_location = :location,
                    updated_at = NOW()
                WHERE id = :id');

        $stmt->execute([
            'location' => $locationName,
            'id' => $businessId,
        ]);
    }

    public function updateConnectionStatus(int $businessId, string $status, ?string $message = null): void
    {
        $stmt = $this->pdo->prepare('UPDATE businesses
                SET connection_status = :status,
                    connection_message = :message,
                    connection_checked_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id');

        $stmt->execute([
            'status' => $status,
            'message' => $message,
            'id' => $businessId,
        ]);
    }

    public function recordLastCheck(int $businessId, int $fetchedCount, int $repliedCount): void
    {
        $stmt = $this->pdo->prepare('UPDATE businesses
                SET last_checked_at = NOW(),
                    last_check_fetched = :fetched,
                    last_check_replied = :replied
                WHERE id = :id');

        $stmt->execute([
            'fetched' => $fetchedCount,
            'replied' => $repliedCount,
            'id' => $businessId,
        ]);
    }

    public function delete(int $businessId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM businesses WHERE id = :id');
        $stmt->execute(['id' => $businessId]);
    }
}
