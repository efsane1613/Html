<?php

namespace App\Reviews;

use App\Gemini\GeminiClient;
use App\Google\GoogleMyBusinessClient;
use App\Support\Logger;
use RuntimeException;

class ReviewResponder
{
    private ReviewRepository $reviewRepository;
    private Logger $logger;

    public function __construct(ReviewRepository $reviewRepository, Logger $logger)
    {
        $this->reviewRepository = $reviewRepository;
        $this->logger = $logger;
    }

    /**
     * @param array<string, mixed> $business
     */
    public function handleBusiness(array $business): void
    {
        $businessId = (int)$business['id'];

        if (empty($business['googleAccessToken'])) {
            throw new RuntimeException(sprintf('Business %s is missing googleAccessToken.', $businessId));
        }

        if (empty($business['geminiApiKey'])) {
            throw new RuntimeException(sprintf('Business %s is missing geminiApiKey.', $businessId));
        }

        $googleClient = new GoogleMyBusinessClient($business['googleAccessToken']);
        $geminiClient = new GeminiClient($business['geminiApiKey'], $business['geminiModel'] ?? 'models/gemini-1.0-pro');
        $promptBuilder = new PromptBuilder();

        $reviews = $googleClient->listReviews($business['googleLocation']);

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
        }
    }
}
