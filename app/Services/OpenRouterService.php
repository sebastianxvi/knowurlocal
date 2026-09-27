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

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => 1000,
        ];

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
            ->retry(1, 350, throw: false)
            ->connectTimeout(3)
            ->timeout(12);

        $response = $request->post(
            'https://openrouter.ai/api/v1/chat/completions',
            $payload
        );

        /*
         * Some OpenRouter models/providers do not support response_format.
         * If strict JSON mode is rejected, retry once without that optional
         * parameter; the translation prompt still requires JSON and the
         * caller validates the returned structure.
         */
        if ($response->failed() && $responseFormat !== null) {
            unset($payload['response_format']);

            $response = $request->post(
                'https://openrouter.ai/api/v1/chat/completions',
                $payload
            );
        }

        if ($response->failed()) {

    /*
     * Record only the HTTP status on the server; provider response bodies may contain sensitive request context.
     *
     * This is useful for diagnosing API failures without exposing
     * provider details to the browser.
     */
    \Log::error('OPENROUTER REQUEST FAILED', [
        'status' => $response->status(),
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