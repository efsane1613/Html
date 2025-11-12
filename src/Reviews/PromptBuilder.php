<?php

namespace App\Reviews;

class PromptBuilder
{
    public function buildPrompt(array $business, array $review): string
    {
        $businessName = $business['name'];
        $comment = $review['comment'] ?? '';
        $rating = $review['starRating'] ?? null;

        $tone = 'samimi, profesyonel ve içten';
        $basePrompt = <<<PROMPT
Sen bir işletmenin müşteri hizmetleri asistanısın. Görevin, müşterilerin Google yorumlarına doğal, kibar ve markayı iyi yansıtan şekilde yanıt vermektir.

Kurallar:
- Tüm yanıtlar Türkçe olacak.
- Üslubun {$tone} olmalı.
- Gerektiğinde emoji kullanabilirsin ama aşırıya kaçma.
- Olumlu yorumlarda teşekkür et ve sadakati teşvik et.
- Olumsuz yorumlarda özür dile, çözüm öner veya iletişim bilgisi bırak.
- Aynı yanıtı iki kere verme.
- Marka adı: {$businessName}.
PROMPT;

        $reviewSummary = sprintf("Kullanıcı yorumu: \"%s\"", $comment);
        if ($rating !== null) {
            $reviewSummary .= sprintf(" (Puan: %s)", $rating);
        }

        $reviewSummary .= "\n\nBu yoruma {$businessName} adına doğal bir şekilde yanıt ver.";

        return $basePrompt . "\n\n" . $reviewSummary;
    }
}
