<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/** Direct Groq transport for the FAQ-matching path. */
class GroqService
{
    public function chat(
        array $messages,
        float $temperature = 0.3,
        ?array $responseFormat = null,
        ?int $timeout = null,
        ?int $connectTimeout = null,
        ?int $maxAttempts = null,
        ?callable $responseValidator = null,
        ?array $candidateModels = null,
        ?array $requestOptions = null
    ): array {
        $apiKey = trim((string) config('services.groq.api_key'));
        $model = trim((string) config('services.groq.model', 'openai/gpt-oss-20b'));
        $baseUrl = rtrim(trim((string) config('services.groq.base_url', 'https://api.groq.com/openai/v1')), '/');

        if ($apiKey === '' || $model === '') {
            throw new RuntimeException('Groq configuration is incomplete.');
        }

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => (int) config('services.groq.max_tokens', 512),
        ];
        if ($responseFormat !== null) {
            $payload['response_format'] = $responseFormat;
        }
        if ($requestOptions !== null) {
            $payload = array_replace_recursive($payload, $requestOptions);
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->connectTimeout($connectTimeout ?? 5)
                ->timeout($timeout ?? 15)
                ->post($baseUrl . '/chat/completions', $payload);
        } catch (\Throwable $e) {
            Log::warning('GROQ REQUEST EXCEPTION', [
                'model' => $model,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            throw new RuntimeException('Groq request failed before a response was received.', 0, $e);
        }

        $rateHeaders = [
            'limit_requests' => $response->header('x-ratelimit-limit-requests'),
            'remaining_requests' => $response->header('x-ratelimit-remaining-requests'),
            'reset_requests' => $response->header('x-ratelimit-reset-requests'),
            'limit_tokens' => $response->header('x-ratelimit-limit-tokens'),
            'remaining_tokens' => $response->header('x-ratelimit-remaining-tokens'),
            'reset_tokens' => $response->header('x-ratelimit-reset-tokens'),
            'retry_after' => $response->header('retry-after'),
        ];

        if (!$response->successful()) {
            Log::warning('GROQ REQUEST FAILED', [
                'model' => $model,
                'status' => $response->status(),
                'rate_limits' => $rateHeaders,
                'body' => mb_substr($response->body(), 0, 1200),
            ]);
            throw new RuntimeException('Groq returned HTTP ' . $response->status() . '.');
        }

        $body = $response->json();
        if (!is_array($body)) {
            Log::warning('GROQ INVALID JSON RESPONSE', [
                'model' => $model,
                'status' => $response->status(),
                'body_preview' => mb_substr($response->body(), 0, 500),
                'rate_limits' => $rateHeaders,
            ]);
            throw new RuntimeException('Groq returned an invalid JSON response.');
        }

        $choice = data_get($body, 'choices.0', []);
        $message = data_get($choice, 'message', []);
        $content = data_get($message, 'content');

        // Accommodate OpenAI-compatible APIs that return text content as parts.
        if (is_array($content)) {
            $content = collect($content)
                ->map(fn ($part) => is_string($part)
                    ? $part
                    : (string) data_get($part, 'text', ''))
                ->implode('');
        }

        $finishReason = data_get($choice, 'finish_reason');
        $usage = data_get($body, 'usage', []);
        $messageKeys = is_array($message) ? array_keys($message) : [];

        if (!is_string($content) || trim($content) === '') {
            $reasoning = data_get($message, 'reasoning')
                ?? data_get($message, 'reasoning_content');

            Log::warning('GROQ EMPTY MESSAGE CONTENT', [
                'model' => data_get($body, 'model', $model),
                'generation_id' => data_get($body, 'id'),
                'http_status' => $response->status(),
                'finish_reason' => $finishReason,
                'usage' => $usage,
                'message_keys' => $messageKeys,
                'reasoning_chars' => is_string($reasoning) ? mb_strlen($reasoning) : null,
                'refusal' => data_get($message, 'refusal'),
                'rate_limits' => $rateHeaders,
                // This is response-only diagnostic data; never log request headers
                // or credentials. Keep the preview short to limit log exposure.
                'response_preview' => mb_substr($response->body(), 0, 500),
            ]);
            throw new RuntimeException('Groq returned an empty message.');
        }

        $result = [
            'choices' => [[
                'message' => array_merge(
                    ['role' => 'assistant', 'content' => $content],
                    is_array($message) ? array_intersect_key($message, array_flip([
                        'refusal',
                        'reasoning',
                        'reasoning_content',
                    ])) : []
                ),
                'finish_reason' => $finishReason,
            ]],
            'model' => data_get($body, 'model', $model),
            'id' => data_get($body, 'id'),
            'usage' => $usage,
        ];

        Log::info('GROQ REQUEST SUCCEEDED', [
            'requested_model' => $model,
            'served_model' => $result['model'],
            'generation_id' => $result['id'],
            'finish_reason' => $finishReason,
            'usage' => $result['usage'],
            'rate_limits' => $rateHeaders,
        ]);

        if ($responseValidator !== null && !$responseValidator($result)) {
            Log::warning('GROQ RESPONSE REJECTED BY VALIDATOR', [
                'model' => $result['model'],
                'generation_id' => $result['id'],
                'finish_reason' => $finishReason,
                'usage' => $usage,
                'content_preview' => mb_substr($content, 0, 500),
            ]);
            throw new RuntimeException('Groq response did not pass FAQ validation.');
        }

        return $result;
    }
}
