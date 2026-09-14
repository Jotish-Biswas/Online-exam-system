<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiExplanationService
{
    public function explain(array $questionData): string
    {
        $apiKey = config('services.gemini.key');
        $model = config('services.gemini.model', 'gemini-2.5-flash-lite');

        if (!$apiKey) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $prompt = <<<'PROMPT'
You are a patient Bengali teacher. Explain and solve the exam question below in simple Bengali.

Rules:
- Clearly state the correct answer and explain why it is correct.
- If the student answer is wrong, explain why; if unanswered, mention that.
- Explain every correct answer for multiple-choice questions.
- Give step-by-step working when useful.
- Do not assume facts outside the question.
- Stay within 200 words.
- Return only the explanation, with no heading and no JSON.

PROMPT;

        $response = Http::timeout(30)
            ->acceptJson()
            ->post(
                'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . urlencode($apiKey),
                [
                    'contents' => [[
                        'parts' => [[
                            'text' => $prompt . json_encode($questionData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]],
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => 500,
                    ],
                ]
            );

        if ($response->failed()) {
            $message = $response->json('error.message') ?: 'Gemini request failed.';
            throw new RuntimeException($message, $response->status());
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        if (!is_string($text) || trim($text) === '') {
            throw new RuntimeException('Gemini returned an empty explanation.');
        }

        return trim($text);
    }
}