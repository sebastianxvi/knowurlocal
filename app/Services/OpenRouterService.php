<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenRouterService
{
    /**
     * Send a conversation to OpenRouter.
     *
     * This service is provider transport only. It does not contain FAQ
     * matching logic.
     */
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
        $apiKey = trim((string) config('services.openrouter.api_key'));
        $model = trim((string) config('services.openrouter.model'));
        $fallbackModels = array_values(array_filter(
            array_map(
                'trim',
                explode(
                    ',',
                    (string) config('services.openrouter.fallback_models', '')
                )
            ),
            static fn (string $value): bool => $value !== ''
        ));

        if ($apiKey === '' || $model === '') {
            throw new RuntimeException(
                'OpenRouter configuration is incomplete.'
            );
        }

        // Prefer an explicit candidate list when the caller knows that model
        // compatibility matters (the FAQ retriever does). Otherwise preserve
        // the application's configured model/fallback chain.
        //
        // We intentionally do NOT depend on openrouter/free for the FAQ
        // retriever: that router can select a reasoning model with a very small
        // completion budget, which is a poor fit for a tiny JSON-only decision.
        $candidateModels = $candidateModels !== null
            ? array_values(array_unique(array_filter(
                array_map('trim', $candidateModels),
                static fn (string $value): bool => $value !== ''
            )))
            : array_values(array_unique([
                $model,
                ...array_slice($fallbackModels, 0, 2),
            ]));

        if ($candidateModels === []) {
            throw new RuntimeException('No OpenRouter candidate models are configured.');
        }

        $basePayload = [
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => (int) config('services.openrouter.max_tokens', 768),
            // Let OpenRouter fail over between providers for the selected model.
            'provider' => [
                'allow_fallbacks' => true,
            ],
        ];

        if ($requestOptions !== null) {
            // Caller-specific options such as `reasoning.effort=none` are merged
            // after the common payload without changing other call sites.
            $basePayload = array_replace_recursive($basePayload, $requestOptions);
        }

        /*
         * Structured output is optional. The FAQ retriever currently passes
         * null intentionally because OpenRouter's free/variable model pool can
         * contain models/providers with different structured-output support.
         */
        if ($responseFormat !== null) {
            $basePayload['response_format'] = $responseFormat;
        }

        $request = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'X-Title' => 'KNOWURLOCAL FAQ Assistant',
            ])
            ->connectTimeout($connectTimeout ?? 5)
            ->timeout($timeout ?? 15);

        $attemptLimit = max(1, $maxAttempts ?? 1);
        $response = null;
        $attempt = 0;
        $lastModel = $model;

        foreach ($candidateModels as $candidateIndex => $candidateModel) {
            $healthKey = $this->candidateHealthKey($candidateModel);

            if (Cache::store(config('services.openrouter.cache_store', 'file'))->has($healthKey)) {
                Log::debug('OPENROUTER CANDIDATE TEMPORARILY SKIPPED', [
                    'requested_model' => $model,
                    'candidate_model' => $candidateModel,
                    'candidate_index' => $candidateIndex,
                ]);
                continue;
            }

            for ($modelAttempt = 1; $modelAttempt <= $attemptLimit; $modelAttempt++) {
                $attempt++;
                $lastModel = $candidateModel;
                $payload = $basePayload + ['model' => $candidateModel];

                try {
                    $response = $request->post(
                        'https://openrouter.ai/api/v1/chat/completions',
                        $payload
                    );
                } catch (\Throwable $e) {
                    Log::warning('OPENROUTER REQUEST EXCEPTION', [
                        'requested_model' => $model,
                        'candidate_model' => $candidateModel,
                        'candidate_index' => $candidateIndex,
                        'attempt' => $modelAttempt,
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                    ]);

                    if ($modelAttempt < $attemptLimit) {
                        usleep(300000 * $modelAttempt);
                        continue;
                    }

                    // A connect/read timeout is a candidate-health problem for
                    // this request path. Temporarily quarantine the model so the
                    // next chat request does not repeat the same slow failure.
                    Cache::store(config('services.openrouter.cache_store', 'file'))->put($healthKey, true, now()->addSeconds(30));
                    break;
                }

                if (!$response->successful()) {
                    $status = $response->status();
                    $body = $response->body();
                    $candidateUnavailable = $status === 403
                        && (
                            str_contains($body, 'only available on agentic harnesses')
                            || str_contains($body, 'unavailable for free')
                            || str_contains($body, 'not available for free')
                        );
                    $retryable = in_array($status, [408, 409, 425, 429, 500, 502, 503, 504], true);

                    Log::warning('OPENROUTER CANDIDATE FAILED', [
                        'requested_model' => $model,
                        'candidate_model' => $candidateModel,
                        'candidate_index' => $candidateIndex,
                        'status' => $status,
                        'attempt' => $modelAttempt,
                        'will_try_next_model' => $retryable || $status === 404 || $candidateUnavailable,
                        'retry_after' => $response->header('Retry-After'),
                        'body' => mb_substr($response->body(), 0, 1000),
                    ]);

                    if ($retryable && $modelAttempt < $attemptLimit) {
                        usleep(300000 * $modelAttempt);
                        continue;
                    }

                    // Rate limits, upstream failures and timeouts should move to
                    // the next independent model. Non-retryable 4xx errors stop
                    // immediately because another model cannot fix a malformed
                    // request or invalid credentials.
                    if ($retryable) {
                        $quarantineSeconds = match ($status) {
                            429 => 60,
                            408, 409, 425 => 30,
                            default => 20,
                        };

                        Cache::store(config('services.openrouter.cache_store', 'file'))->put($healthKey, true, now()->addSeconds($quarantineSeconds));
                        break;
                    }

                    /*
                     * A 404 can mean that this particular model variant is no
                     * longer available (for example, a free model moving to a
                     * paid-only slug). That must NOT terminate the whole
                     * failover chain.
                     */
                    if ($status === 404) {
                        Cache::store(config('services.openrouter.cache_store', 'file'))->put($healthKey, true, now()->addMinutes(10));
                        break;
                    }

                    /*
                     * Some OpenRouter 403 responses are candidate-specific gates
                     * rather than account authentication failures.
                     */
                    if ($candidateUnavailable) {
                        Cache::store(config('services.openrouter.cache_store', 'file'))->put($healthKey, true, now()->addMinutes(10));
                        break;
                    }

                    /*
                     * Authentication, permission, and malformed-request
                     * failures are application/configuration failures. Trying
                     * another model cannot repair those.
                     */
                    break 2;
                }

                $json = $response->json();

                if (!is_array($json)) {
                    Log::warning('OPENROUTER INVALID JSON RESPONSE', [
                        'requested_model' => $model,
                        'candidate_model' => $candidateModel,
                        'candidate_index' => $candidateIndex,
                        'attempt' => $modelAttempt,
                    ]);

                    if ($modelAttempt < $attemptLimit) {
                        usleep(300000 * $modelAttempt);
                        continue;
                    }

                    Cache::store(config('services.openrouter.cache_store', 'file'))->put($healthKey, true, now()->addSeconds(20));
                    break;
                }

                $content = $this->extractTextContent($json);

                if ($content !== null) {
                    $json['choices'][0]['message']['content'] = $content;

                    /*
                     * A non-empty response is not necessarily a usable response
                     * for the caller. The FAQ retriever, for example, requires a
                     * valid retrieval decision JSON. Let the caller validate the
                     * semantic contract here so transport-level failover can move
                     * to the next independent model when a model answers with
                     * prose, truncated JSON, or another unusable format.
                     */
                    if ($responseValidator !== null) {
                        try {
                            $accepted = (bool) $responseValidator($json);
                        } catch (\Throwable $e) {
                            $accepted = false;

                            Log::warning('OPENROUTER RESPONSE VALIDATOR EXCEPTION', [
                                'requested_model' => $model,
                                'candidate_model' => $candidateModel,
                                'candidate_index' => $candidateIndex,
                                'attempt' => $modelAttempt,
                                'exception' => get_class($e),
                                'message' => $e->getMessage(),
                            ]);
                        }

                        if (!$accepted) {
                            Log::warning('OPENROUTER CANDIDATE REJECTED BY RESPONSE VALIDATOR', [
                                'requested_model' => $model,
                                'candidate_model' => $candidateModel,
                                'candidate_index' => $candidateIndex,
                                'attempt' => $modelAttempt,
                                'served_model' => $json['model'] ?? null,
                                'finish_reason' => is_array($json['choices'][0] ?? null)
                                    ? ($json['choices'][0]['finish_reason'] ?? null)
                                    : null,
                                'content_preview' => mb_substr($content, 0, 500),
                            ]);

                            /*
                             * The transport succeeded, but the inference result
                             * did not satisfy the caller's contract. Do not return
                             * it as a success; try the next candidate model.
                             * A short quarantine prevents an immediately repeated
                             * malformed/truncated response from dominating the chain.
                             */
                            Cache::store(config('services.openrouter.cache_store', 'file'))->put($healthKey, true, now()->addSeconds(20));
                            break;
                        }
                    }

                    Log::debug('OPENROUTER CANDIDATE SUCCEEDED', [
                        'requested_model' => $model,
                        'candidate_model' => $candidateModel,
                        'candidate_index' => $candidateIndex,
                        'served_model' => $json['model'] ?? null,
                    ]);
                    return $json;
                }

                $choice = $json['choices'][0] ?? [];
                $message = is_array($choice) ? ($choice['message'] ?? null) : null;
                $usage = is_array($json['usage'] ?? null) ? $json['usage'] : [];
                $reasoningTokens = $usage['completion_tokens_details']['reasoning_tokens']
                    ?? $usage['completionTokensDetails']['reasoningTokens']
                    ?? null;

                Log::warning('OPENROUTER EMPTY MESSAGE CONTENT', [
                    'requested_model' => $model,
                    'candidate_model' => $candidateModel,
                    'candidate_index' => $candidateIndex,
                    'attempt' => $modelAttempt,
                    'finish_reason' => is_array($choice) ? ($choice['finish_reason'] ?? null) : null,
                    'message_keys' => is_array($message) ? array_keys($message) : [],
                    'refusal' => is_array($message) ? ($message['refusal'] ?? null) : null,
                    'reasoning_tokens' => $reasoningTokens,
                    'usage' => $usage,
                ]);

                if ($modelAttempt < $attemptLimit) {
                    usleep(300000 * $modelAttempt);
                    continue;
                }

                // Empty output is treated as a failed candidate so the next
                // independent model can answer the same retrieval request.
                Cache::store(config('services.openrouter.cache_store', 'file'))->put($healthKey, true, now()->addSeconds(20));
                break;
            }
        }

        if (!$response || $response->failed()) {
            $status = $response?->status();

            Log::error('OPENROUTER REQUEST FAILED', [
                'status' => $status,
                'model' => $lastModel,
                'requested_model' => $model,
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

        throw new RuntimeException(
            'OpenRouter returned an empty chat response.'
        );
    }

    /**
     * Keep temporarily unhealthy model candidates out of the hot path.
     *
     * Free endpoints are shared pools and can rate-limit or stall without the
     * model itself being permanently unavailable. A short per-model quarantine
     * prevents every user request from repeating the same failed candidate.
     */
    private function candidateHealthKey(string $candidateModel): string
    {
        return 'knowurlocal:openrouter:candidate-unhealthy:' . sha1($candidateModel);
    }

    /**
     * Normalize the provider's message content into plain text.
     *
     * OpenAI-compatible providers usually return a string, while some return
     * an array of text/content blocks. Empty content is treated as an invalid
     * inference result so the transport layer can retry it.
     */
    private function extractTextContent(array $json): ?string
    {
        $message = $json['choices'][0]['message'] ?? null;

        if (!is_array($message)) {
            return null;
        }

        $content = $message['content'] ?? null;

        if (is_string($content)) {
            return trim($content) !== '' ? $content : null;
        }

        if (!is_array($content)) {
            return null;
        }

        $textParts = [];

        foreach ($content as $part) {
            if (is_string($part) && trim($part) !== '') {
                $textParts[] = $part;
                continue;
            }

            if (!is_array($part)) {
                continue;
            }

            $text = $part['text'] ?? $part['content'] ?? null;
            if (is_string($text) && trim($text) !== '') {
                $textParts[] = $text;
            }
        }

        return $textParts === [] ? null : implode("\n", $textParts);

    }
}
