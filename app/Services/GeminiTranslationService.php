<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

/**
 * Small Gemini REST adapter for the existing FAQ translation pipeline.
 *
 * It intentionally exposes the same chat-shaped contract the translator uses,
 * while the public chatbot remains on its existing provider/service.
 */
class GeminiTranslationService
{
    public function chat(
        array $messages,
        float $temperature = 0.0,
        ?array $responseFormat = null,
        ?int $timeout = null,
        ?int $connectTimeout = null,
        ?int $maxAttempts = null,
        ?callable $responseValidator = null,
        ?array $candidateModels = null,
        ?array $requestOptions = null
    ): array {
        $apiKey = trim((string) config('services.gemini_translation.api_key'));
        $model = trim((string) config('services.gemini_translation.model', 'gemini-3.1-flash-lite'));

        if ($apiKey === '' || $model === '') {
            throw new RuntimeException('Gemini translation configuration is incomplete.');
        }

        $systemInstruction = '';
        $contents = [];
        foreach ($messages as $message) {
            $role = strtolower((string) ($message['role'] ?? 'user'));
            $content = (string) ($message['content'] ?? '');

            if ($role === 'system' || $role === 'developer') {
                $systemInstruction .= ($systemInstruction === '' ? '' : "\n\n") . $content;
                continue;
            }

            // Translation is a single-turn task. Treat assistant history as user
            // context if it ever appears, but current callers only send one user item.
            $contents[] = [
                'role' => $role === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $content]],
            ];
        }

        if ($contents === []) {
            throw new RuntimeException('Gemini translation request has no input content.');
        }

        $inputCharacters = 0;
        foreach ($contents as $content) {
            foreach ($content['parts'] as $part) {
                $inputCharacters += mb_strlen((string) ($part['text'] ?? ''), 'UTF-8');
            }
        }
        $maxInputCharacters = max(1000, (int) config('services.gemini_translation.max_input_characters', 12000));
        if ($inputCharacters > $maxInputCharacters) {
            throw new RuntimeException('Gemini translation input exceeds the configured character limit.');
        }

        // Honor a short provider cooldown after a 429 so recovery paths cannot
        // repeatedly hit a quota-limited API.
        if (Cache::has('gemini-translation:provider-cooldown')) {
            throw new RuntimeException('Gemini translation is temporarily paused after a provider rate limit.');
        }

        // Hard global budgets, deliberately below the model's displayed 15 RPM.
        // Every outbound attempt consumes budget, including provider failures.
        $perMinuteLimit = max(1, (int) config('services.gemini_translation.requests_per_minute', 8));
        $perDayLimit = max(1, (int) config('services.gemini_translation.requests_per_day', 50));
        $minuteKey = 'gemini-translation:requests:minute';
        $dayKey = 'gemini-translation:requests:day:' . now()->toDateString();

        if (RateLimiter::tooManyAttempts($minuteKey, $perMinuteLimit)) {
            throw new RuntimeException('Gemini translation rate limit reached. Please wait a minute and try again.');
        }
        if (RateLimiter::tooManyAttempts($dayKey, $perDayLimit)) {
            throw new RuntimeException('The daily Gemini translation budget has been reached. Please try again tomorrow.');
        }

        // Count before dispatch so concurrent requests cannot all pass the
        // check without incrementing the shared limiter.
        RateLimiter::hit($minuteKey, 60);
        RateLimiter::hit($dayKey, 86400);

        $generationConfig = [
            'temperature' => max(0.0, min(1.0, $temperature)),
            'maxOutputTokens' => min(
                max(128, (int) config('services.gemini_translation.max_output_tokens', 4096)),
                max(128, (int) data_get($requestOptions, 'max_tokens', config('services.gemini_translation.max_output_tokens', 4096)))
            ),
        ];

        if ($responseFormat !== null) {
            $generationConfig['responseMimeType'] = 'application/json';
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => $generationConfig,
        ];
        if ($systemInstruction !== '') {
            $payload['systemInstruction'] = ['parts' => [['text' => $systemInstruction]]];
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . rawurlencode($model) . ':generateContent';

        try {
            $response = Http::withHeaders([
                'x-goog-api-key' => $apiKey,
                'Accept' => 'application/json',
            ])
                ->asJson()
                ->connectTimeout(max(2, min(10, $connectTimeout ?? 5)))
                ->timeout(max(5, min(30, $timeout ?? 20)))
                ->post($url, $payload);
        } catch (\Throwable $e) {
            Log::warning('Gemini FAQ translation transport failed.', [
                'model' => $model,
                'exception' => get_class($e),
            ]);
            throw new RuntimeException('Gemini translation service could not be reached.', 0, $e);
        }

        if (!$response->successful()) {
            $status = $response->status();
            Log::warning('Gemini FAQ translation returned an HTTP error.', [
                'model' => $model,
                'status' => $status,
            ]);

            if ($status === 429) {
                Cache::put('gemini-translation:provider-cooldown', true, now()->addMinutes(15));
                throw new RuntimeException('Gemini translation quota or rate limit reached.');
            }
            if (in_array($status, [401, 403], true)) {
                throw new RuntimeException('Gemini translation API key or project access was rejected.');
            }
            throw new RuntimeException('Gemini translation request failed.');
        }

        $json = $response->json();
        $parts = data_get($json, 'candidates.0.content.parts', []);
        $text = '';
        foreach (is_array($parts) ? $parts : [] as $part) {
            if (isset($part['text']) && is_string($part['text'])) {
                $text .= $part['text'];
            }
        }

        if (trim($text) === '') {
            Log::warning('Gemini FAQ translation returned no text.', [
                'model' => $model,
                'finish_reason' => data_get($json, 'candidates.0.finishReason'),
            ]);
            throw new RuntimeException('Gemini returned an empty translation response.');
        }

        // Adapt Gemini's response to the stable shape expected by the existing
        // translator and its JSON/semantic validation callbacks.
        $adapted = [
            'choices' => [[
                'message' => ['content' => $text],
                'finish_reason' => data_get($json, 'candidates.0.finishReason'),
            ]],
        ];

        if ($responseValidator !== null && !$responseValidator($adapted)) {
            throw new RuntimeException('Gemini response did not pass translation validation.');
        }

        return $adapted;
    }
}
