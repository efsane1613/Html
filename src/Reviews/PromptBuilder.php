<?php

namespace App\Reviews;

class PromptBuilder
{
    public function buildPrompt(array $business, array $review): string
    {
        $businessName = $business['name'];
        $comment = $review['comment'] ?? '';
        $rating = $review['starRating'] ?? null;
        $language = $this->detectLanguage($comment);

        $languageName = [
            'tr' => 'Turkish',
            'en' => 'English',
            'de' => 'German',
        ][$language] ?? 'Turkish';

        $tone = 'warm, professional and sincere';
        $basePrompt = <<<PROMPT
You are the customer care assistant of the business "{$businessName}". Your job is to respond to Google reviews in a natural way that reflects the brand positively.

Guidelines:
- Write the reply in {$languageName} (match the review language among Turkish, English or German).
- Keep the tone {$tone}.
- Emojis are allowed sparingly when they feel natural.
- Thank happy customers and encourage loyalty.
- Apologise for negative experiences and suggest a solution or contact option.
- Never reuse the same reply wording twice.
- Clearly mention the brand name {$businessName} when appropriate.
- Mirror the customer's language choice; do not switch languages.
PROMPT;

        $reviewSummary = sprintf("Review text: \"%s\"", $comment);
        if ($rating !== null) {
            $reviewSummary .= sprintf(" (Rating: %s)", $rating);
        }

        $reviewSummary .= "\n\nCraft the full reply on behalf of {$businessName}.";

        return $basePrompt . "\n\n" . $reviewSummary;
    }

    private function detectLanguage(string $text): string
    {
        $lower = mb_strtolower($text, 'UTF-8');

        if ($lower === '') {
            return 'tr';
        }

        if (preg_match('/[ığüşçİĞÜŞÇ]/u', $text)) {
            return 'tr';
        }

        if (preg_match('/[äöüßÄÖÜẞ]/u', $text)) {
            return 'de';
        }

        if (preg_match('/\b(danke|bitte|hallo|tschüss|grüße)\b/u', $lower)) {
            return 'de';
        }

        if (preg_match('/\b(thank|please|hello|hi|great|awesome|love|perfect)\b/u', $lower)) {
            return 'en';
        }

        if (preg_match('/[a-z]/iu', $text)) {
            return 'en';
        }

        return 'tr';
    }
}
