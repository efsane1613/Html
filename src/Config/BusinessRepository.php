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
        if (empty($data['name']) || empty($data['google_location']) || empty($data['google_access_token']) || empty($data['gemini_api_key'])) {
            throw new RuntimeException('Name, Google location, Google access token and Gemini API key are required.');
        }

        $sql = 'INSERT INTO businesses (name, google_location, google_access_token, gemini_api_key, gemini_model, created_at, updated_at)
                VALUES (:name, :google_location, :google_access_token, :gemini_api_key, :gemini_model, NOW(), NOW())';

        $stmt = $this->pdo->prepare($sql);

        $params = [
            'name' => $data['name'],
            'google_location' => $data['google_location'],
            'google_access_token' => $data['google_access_token'],
            'gemini_api_key' => $data['gemini_api_key'],
            'gemini_model' => $data['gemini_model'] ?? 'gemini-2.5-flash-lite-preview-09-2025',
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
            'googleAccessToken' => (string)$row['google_access_token'],
            'geminiApiKey' => (string)$row['gemini_api_key'],
            'geminiModel' => $row['gemini_model'] ?: 'gemini-2.5-flash-lite-preview-09-2025',
            'lastCheckedAt' => $row['last_checked_at'] ?? null,
            'lastCheckFetched' => isset($row['last_check_fetched']) ? (int)$row['last_check_fetched'] : 0,
            'lastCheckReplied' => isset($row['last_check_replied']) ? (int)$row['last_check_replied'] : 0,
            'createdAt' => $row['created_at'] ?? null,
            'updatedAt' => $row['updated_at'] ?? null,
        ];
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
}
