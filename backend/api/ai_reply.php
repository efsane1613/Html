<?php
$config = require __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/utils.php';

function generateAiReply(string $comment, string $businessName): string
{
    global $config;
    $prompt = "Profesyonel, nazik ve kurumsal bir Alman yorumu yanıtı oluştur. İşletme adı: {$businessName}. Müşteri yorumu: {$comment}";
    $payload = [
        'contents' => [
            ['parts' => [['text' => $prompt]]]
        ]
    ];
    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/{$config['ai']['model']}:generateContent?key={$config['ai']['api_key']}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Danke für Ihr Feedback! Wir melden uns in Kürze.';
    return trim($text);
}
