<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenRouterService
{
    /**
     * Send a conversation to OpenRouter.
     *
     * This class is deliberately generic. FAQ retrieval and translation decide
     * what the model should do; this service only handles the provider request,
     * retries, and response validation.
     */
    public function chat(
        array $messages,
        float $temperature = 0.3,
        ?array $responseFormat = null,
        ?int $timeout = null,
        ?int $connectTimeout = null,
        ?int $maxAttempts = null
    ): array {
        $apiKey = trim((string) config('services.openrouter.api_key'));
        $model = trim((string) config('services.openrouter.model'));

        if ($apiKey === '' || $model === '') {
            throw new RuntimeException(
                'OpenRouter configuration is incomplete.'
            );
        }

        /*
         * OpenRouter supports model fallback through the `models` request
         * parameter. Keep the configured primary model first.
         */
        $fallbackModels = array_values(array_filter(
            array_map(
                'trim',
                explode(
                    ',',
                    (string) config(
                        'services.openrouter.fallback_models',
                        ''
                    )
                )
            ),
            static fn (string $value): bool => $value !== ''
        ));

        $models = array_values(array_unique([
            $model,
            ...$fallbackModels,
        ]));

        /*
         * Keep the request intentionally small on the output side.
         * FAQ retrieval only needs one short JSON object.
         */
        $payload = [
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => 160,
        ];

        /*
         * Use the documented OpenRouter `models` fallback mechanism when
         * multiple models were configured. With one model, use the normal
         * `model` field.
         */
        if (count($models) > 1) {
            $payload['models'] = $models;
        } else {
            $payload['model'] = $model;
        }

        if ($responseFormat !== null) {
            $payload['response_format'] = $responseFormat;
        }

        $request = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'X-Title' => 'KNOWURLOCAL FAQ Assistant',
            ])
            ->connectTimeout($connectTimeout ?? 3)
            ->timeout($timeout ?? 10);

        $attemptLimit = max(1, $maxAttempts ?? 2);

        $response = null;
        $responseFormatRemoved = false;

        for ($attempt = 1; $attempt <= $attemptLimit; $attempt++) {
            $response = $request->post(
                'https://openrouter.ai/api/v1/chat/completions',
                $payload
            );

            if ($response->successful()) {
                break;
            }

            /*
             * Some model/provider combinations reject structured-output
             * parameters. Retry the exact same request without that optional
             * parameter before treating the provider call as failed.
             */
            if (
                $responseFormat !== null
                && !$responseFormatRemoved
                && isset($payload['response_format'])
            ) {
                unset($payload['response_format']);
                $responseFormatRemoved = true;
                continue;
            }

            /*
             * Retry transient failures only. Never repeatedly retry
             * authentication or malformed-request failures.
             */
            if (
                in_array(
                    $response->status(),
                    [408, 409, 425, 429, 500, 502, 503, 504],
                    true
                )
                && $attempt < $attemptLimit
            ) {
                usleep(500000 * $attempt);
                continue;
            }

            break;
        }

        if (!$response || $response->failed()) {
            $status = $response?->status();

            /*
             * The provider body is logged server-side for diagnosis. It is
             * never returned to the browser because it may contain provider
             * metadata or request context.
             */
            Log::error('OPENROUTER REQUEST FAILED', [
                'status' => $status,
                'model' => $model,
                'attempts' => $attempt,
                'body' => $response
                    ? mb_substr($response->body(), 0, 2000)
                    : null,
            ]);

            throw new RuntimeException(
                'OpenRouter request failed'
                . ($status !== null ? " (HTTP {$status})." : '.')
            );
        }

        $json = $response->json();

        if (!is_array($json)) {
            throw new RuntimeException(
                'OpenRouter returned an invalid JSON response.'
            );
        }

        if (!isset($json['choices'][0]['message']['content'])) {
            Log::error('OPENROUTER RESPONSE MISSING MESSAGE CONTENT', [
                'model' => $model,
                'response_keys' => array_keys($json),
            ]);

            throw new RuntimeException(
                'OpenRouter returned an invalid chat response.'
            );
        }

        return $json;
    }
}
