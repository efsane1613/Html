<?php

namespace App\Reviews;

use DateTimeImmutable;
use Exception;
use PDO;
use PDOException;
use RuntimeException;

class ReviewRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forBusiness(int $businessId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM review_logs WHERE business_id = :business_id ORDER BY review_update_time DESC, id DESC');
        $stmt->execute(['business_id' => $businessId]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map([$this, 'mapRow'], $rows);
    }

    public function findByGoogleName(int $businessId, string $googleReviewName): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM review_logs WHERE business_id = :business_id AND google_review_name = :google_review_name');
        $stmt->execute([
            'business_id' => $businessId,
            'google_review_name' => $googleReviewName,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row ? $this->mapRow($row) : null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM review_logs WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        return $row ? $this->mapRow($row) : null;
    }

    /**
     * @param array<string, mixed> $review
     * @return array<string, mixed>
     */
    public function upsertReview(int $businessId, array $review): array
    {
        $googleReviewName = $review['name'] ?? null;
        if ($googleReviewName === null) {
            throw new RuntimeException('Review record is missing a name.');
        }

        $existing = $this->findByGoogleName($businessId, $googleReviewName);

        $data = [
            'business_id' => $businessId,
            'google_review_name' => $googleReviewName,
            'reviewer_name' => $review['reviewer']['displayName'] ?? null,
            'comment' => $review['comment'] ?? null,
            'rating' => $this->convertStarRating($review['starRating'] ?? null),
            'review_update_time' => $this->normalizeDate($review['updateTime'] ?? null),
            'raw_review' => $this->encodeJson($review),
        ];

        if ($existing) {
            $sql = 'UPDATE review_logs
                    SET reviewer_name = :reviewer_name,
                        comment = :comment,
                        rating = :rating,
                        review_update_time = :review_update_time,
                        raw_review = :raw_review,
                        updated_at = NOW()
                    WHERE id = :id';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'reviewer_name' => $data['reviewer_name'],
                'comment' => $data['comment'],
                'rating' => $data['rating'],
                'review_update_time' => $data['review_update_time'],
                'raw_review' => $data['raw_review'],
                'id' => $existing['id'],
            ]);

            return $this->findById($existing['id']);
        }

        $sql = 'INSERT INTO review_logs (business_id, google_review_name, reviewer_name, comment, rating, review_update_time, raw_review, created_at, updated_at)
                VALUES (:business_id, :google_review_name, :reviewer_name, :comment, :rating, :review_update_time, :raw_review, NOW(), NOW())';

        $stmt = $this->pdo->prepare($sql);

        try {
            $stmt->execute($data);
        } catch (PDOException $exception) {
            throw new RuntimeException('Failed to store review: ' . $exception->getMessage(), 0, $exception);
        }

        $id = (int)$this->pdo->lastInsertId();
        return $this->findById($id) ?? [];
    }

    public function recordReply(int $reviewId, string $replyText, ?string $repliedAt = null, string $source = 'auto'): void
    {
        $sql = 'UPDATE review_logs
                SET reply_text = :reply_text,
                    reply_source = :reply_source,
                    replied_at = :replied_at,
                    updated_at = NOW()
                WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'reply_text' => $replyText,
            'reply_source' => $source,
            'replied_at' => $this->normalizeDate($repliedAt) ?? (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'id' => $reviewId,
        ]);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapRow(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'business_id' => (int)$row['business_id'],
            'google_review_name' => $row['google_review_name'],
            'reviewer_name' => $row['reviewer_name'],
            'comment' => $row['comment'],
            'rating' => $row['rating'] !== null ? (int)$row['rating'] : null,
            'review_update_time' => $row['review_update_time'],
            'reply_text' => $row['reply_text'],
            'reply_source' => $row['reply_source'],
            'replied_at' => $row['replied_at'],
            'raw_review' => $row['raw_review'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }

    private function normalizeDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $date = new DateTimeImmutable($value);
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $exception) {
            return null;
        }
    }

    private function encodeJson(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION) ?: '';
    }

    /**
     * @param string|int|null $rating
     */
    private function convertStarRating($rating): ?int
    {
        if ($rating === null || $rating === '') {
            return null;
        }

        if (is_numeric($rating)) {
            return (int)$rating;
        }

        $map = [
            'ONE' => 1,
            'TWO' => 2,
            'THREE' => 3,
            'FOUR' => 4,
            'FIVE' => 5,
        ];

        $upper = strtoupper((string)$rating);
        return $map[$upper] ?? null;
    }
}
