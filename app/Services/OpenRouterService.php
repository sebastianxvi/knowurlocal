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
    public function chat(array $messages, float $temperature = 0.3): array
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

        // Send the request to OpenRouter.
        $response = Http::withToken($apiKey)
            ->acceptJson()

            // Prevent an external provider from keeping
            // the Laravel request open indefinitely.
            ->timeout(30)

            ->post(
                'https://openrouter.ai/api/v1/chat/completions',
                [
                    // Tell OpenRouter which model to use.
                    'model' => $model,

                    // Send the conversation prepared by the caller.
                    'messages' => $messages,

                    // Keep translation output relatively deterministic.
                    'temperature' => $temperature,

                    // Prevent unnecessarily large AI responses.
                    'max_tokens' => 1000,
                ]
            );

        if ($response->failed()) {

    /*
     * Record the HTTP status and provider response on the server.
     *
     * This is useful for diagnosing API failures without exposing
     * provider details to the browser.
     */
    \Log::error('OPENROUTER REQUEST FAILED', [
        'status' => $response->status(),
        'body' => $response->body(),
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