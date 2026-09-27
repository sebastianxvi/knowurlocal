<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenRouterService
{
    /**
     * Send a conversation to the configured OpenRouter model.
     *
     * This service is responsible only for communicating
     * with OpenRouter. It does not know anything about FAQs.
     */
    public function chat(array $messages, float $temperature = 0.3, ?array $responseFormat = null): array
    {
        // Read the secret from Laravel's server-side configuration.
        // The API key must never come from the browser.
        $apiKey = config('services.openrouter.api_key');

        // Read the selected AI model from server-side configuration.
        $model = config('services.openrouter.model');

        // Refuse to make an external request if configuration is incomplete.
        if (!$apiKey || !$model) {
            throw new RuntimeException(
                'OpenRouter configuration is incomplete.'
            );
        }

        /*
         * OpenRouter can fail over to another model when the selected model
         * or provider is temporarily unavailable. Keep the primary model
         * configurable, but provide a sane secondary model so one provider
         * outage does not break FAQ preparation.
         */
        $fallbackModels = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) config(
                'services.openrouter.fallback_models',
                'deepseek/deepseek-chat-v3.1'
            ))
        )));

        $models = array_values(array_unique(
            array_filter(
                array_merge([$model], $fallbackModels),
                static fn ($value) => $value !== ''
            )
        ));

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => 4000,
        ];

        /*
         * OpenRouter's `models` array is the provider/model failover path.
         * It is ignored when it only contains the primary model.
         */
        if (count($models) > 1) {
            $payload['models'] = $models;
        }

        if ($responseFormat !== null) {
            $payload['response_format'] = $responseFormat;
        }

        /*
         * AI calls are external and can occasionally fail transiently.
         * Retry once for transport/provider errors.
         */
        $request = Http::withToken($apiKey)
            ->acceptJson()
            ->withHeaders([
                'X-Title' => 'KNOWURLOCAL FAQ Assistant',
            ])
            ->connectTimeout(5)
            ->timeout(30);

        /*
         * Do not rely on a single HTTP attempt. A 429/5xx/provider timeout
         * is a normal transient failure for an external AI service.
         */
        $response = null;
        $lastStatus = null;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $response = $request->post(
                'https://openrouter.ai/api/v1/chat/completions',
                $payload
            );

            $lastStatus = $response->status();

            if ($response->successful()) {
                break;
            }

            /*
             * Some OpenRouter models/providers do not support response_format.
             * Removing only this optional field is safer than changing the
             * actual translation request.
             */
            if ($responseFormat !== null && isset($payload['response_format'])) {
                unset($payload['response_format']);
                continue;
            }

            /*
             * Retry transient provider/rate-limit failures once. Do not
             * repeatedly hammer authentication or validation failures.
             */
            if (in_array($response->status(), [408, 409, 425, 429, 500, 502, 503, 504], true)) {
                usleep(400000);
                continue;
            }

            break;
        }

        if ($response->failed()) {

    /*
     * Record only the HTTP status on the server; provider response bodies may contain sensitive request context.
     *
     * This is useful for diagnosing API failures without exposing
     * provider details to the browser.
     */
    \Log::error('OPENROUTER REQUEST FAILED', [
        'status' => $lastStatus ?? $response?->status(),
    ]);

    /*
     * Keep the exception intentionally generic.
     *
     * The provider's response must not be sent to the browser.
     */
    throw new RuntimeException(
        'OpenRouter request failed.'
    );
}

        // Convert the JSON response into a PHP array.
        $json = $response->json();

        // Verify that the expected chat-completion structure exists.
        if (!isset($json['choices'][0]['message']['content'])) {
            throw new RuntimeException(
                'OpenRouter returned an invalid response.'
            );
        }

        // Return the validated provider response
        // to the translation service.
        return $json;
    }
}