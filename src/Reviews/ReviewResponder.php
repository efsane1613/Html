<?php

namespace App\Reviews;

use App\Config\BusinessRepository;
use App\Gemini\GeminiClient;
use App\Google\GoogleMyBusinessClient;
use App\Google\GoogleOAuthClient;
use App\Support\Logger;
use DateInterval;
use DateTimeImmutable;
use Exception;
use RuntimeException;

class ReviewResponder
{
    private ReviewRepository $reviewRepository;
    private BusinessRepository $businessRepository;
    private Logger $logger;

    public function __construct(ReviewRepository $reviewRepository, BusinessRepository $businessRepository, Logger $logger)
    {
        $this->reviewRepository = $reviewRepository;
        $this->businessRepository = $businessRepository;
        $this->logger = $logger;
    }

    /**
     * @param array<string, mixed> $business
     */
    /**
     * @return array{fetched:int,replied:int}
     */
    public function handleBusiness(array $business): array
    {
        $businessId = (int)$business['id'];

        $accessToken = $this->resolveAccessToken($businessId, $business);

        if (empty($business['geminiApiKey'])) {
            throw new RuntimeException(sprintf('Business %s is missing geminiApiKey.', $businessId));
        }

        $googleClient = new GoogleMyBusinessClient($accessToken);
        $geminiClient = new GeminiClient(
            $business['geminiApiKey'],
            $business['geminiModel'] ?? 'gemini-2.5-flash-lite-preview-09-2025'
        );
        $promptBuilder = new PromptBuilder();

        $reviews = $googleClient->listReviews($business['googleLocation']);
        $replyCount = 0;

        foreach ($reviews as $review) {
            $reviewName = $review['name'] ?? null;
            if ($reviewName === null) {
                continue;
            }

            $stored = $this->reviewRepository->upsertReview($businessId, $review);

            if (!empty($review['reviewReply']['comment'])) {
                $this->reviewRepository->recordReply(
                    $stored['id'],
                    (string)$review['reviewReply']['comment'],
                    $review['reviewReply']['updateTime'] ?? $review['reviewReply']['createTime'] ?? null,
                    'google'
                );
                continue;
            }

            if (!empty($stored['reply_text'])) {
                continue; // already replied earlier
            }

            $prompt = $promptBuilder->buildPrompt($business, $review);
            $reply = $geminiClient->generateReply($prompt);

            $googleClient->replyToReview($reviewName, $reply);
            $this->reviewRepository->recordReply($stored['id'], $reply);

            $this->logger->info('Replied to review', [
                'businessId' => $businessId,
                'reviewName' => $reviewName,
                'reply' => $reply,
            ]);

            $replyCount++;
        }

        return [
            'fetched' => count($reviews),
            'replied' => $replyCount,
        ];
    }

    private function resolveAccessToken(int $businessId, array $business): string
    {
        $accessToken = $business['googleAccessToken'] ?? '';
        $expiresAt = $business['googleAccessTokenExpiresAt'] ?? null;

        $shouldRefresh = $accessToken === '';

        if ($expiresAt && !$shouldRefresh) {
            try {
                $expiry = new DateTimeImmutable($expiresAt);
                $now = new DateTimeImmutable();
                $buffer = new DateInterval('PT120S');
                if ($expiry <= $now->add($buffer)) {
                    $shouldRefresh = true;
                }
            } catch (Exception $exception) {
                $this->logger->warning('Failed to parse token expiry, forcing refresh', [
                    'businessId' => $businessId,
                    'expiresAt' => $expiresAt,
                    'error' => $exception->getMessage(),
                ]);
                $shouldRefresh = true;
            }
        }

        if (!$shouldRefresh) {
            return $accessToken;
        }

        $refreshToken = $business['googleRefreshToken'] ?? null;
        if (empty($refreshToken)) {
            throw new RuntimeException('Google refresh token bulunamadı. Yönetim panelinden "Bağlantıyı Test Et" seçeneğini kullanarak yetkilendirme kodu girin.');
        }

        $this->logger->info('Refreshing Google access token', ['businessId' => $businessId]);

        $oauthClient = new GoogleOAuthClient($business['googleClientId'], $business['googleClientSecret']);
        $tokenResponse = $oauthClient->refreshAccessToken($refreshToken);

        $newAccessToken = $tokenResponse['access_token'];
        $newRefreshToken = $tokenResponse['refresh_token'] ?? null;
        $expiresIn = $tokenResponse['expires_in'] ?? null;

        $expiresAtFormatted = null;
        if ($expiresIn !== null) {
            $expiresAtFormatted = (new DateTimeImmutable())
                ->add(new DateInterval('PT' . max(0, (int)$expiresIn) . 'S'))
                ->format('Y-m-d H:i:s');
        }

        $this->businessRepository->updateTokens($businessId, $newAccessToken, $newRefreshToken, $expiresAtFormatted);

        return $newAccessToken;
    }
}
