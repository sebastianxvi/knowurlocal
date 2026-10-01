<?php

namespace App\Support;

final class PrivateStorageDiagnostics
{
    private static function hasValue(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    private static function processEnvironmentVariablePresent(
        string $name
    ): bool {
        $value = getenv($name);

        if (self::hasValue($value)) {
            return true;
        }

        return self::hasValue($_ENV[$name] ?? null)
            || self::hasValue($_SERVER[$name] ?? null);
    }

    public static function context(): array
    {
        $disk = (array) config('filesystems.disks.private', []);
        $endpoint = $disk['endpoint'] ?? null;
        $endpointParts = is_string($endpoint)
            ? parse_url($endpoint)
            : false;

        $key = $disk['key'] ?? null;
        $secret = $disk['secret'] ?? null;

        return [
            'storage_disk' => 'private',
            'storage_driver' => $disk['driver'] ?? null,

            // Resolved Laravel disk configuration.
            'access_key_present' => self::hasValue($key),
            'secret_key_present' => self::hasValue($secret),

            // Actual process environment.
            'process_access_key_present' =>
                self::processEnvironmentVariablePresent(
                    'AWS_ACCESS_KEY_ID'
                ),
            'process_secret_key_present' =>
                self::processEnvironmentVariablePresent(
                    'AWS_SECRET_ACCESS_KEY'
                ),

            // Laravel environment helper.
            'laravel_env_access_key_present' =>
                self::hasValue(env('AWS_ACCESS_KEY_ID')),
            'laravel_env_secret_key_present' =>
                self::hasValue(env('AWS_SECRET_ACCESS_KEY')),

            // Configuration cache status.
            'configuration_cached' =>
                app()->configurationIsCached(),

            'region' => $disk['region'] ?? null,
            'bucket' => $disk['bucket'] ?? null,
            'endpoint_host' => is_array($endpointParts)
                ? ($endpointParts['host'] ?? null)
                : null,
            'path_style_endpoint' =>
                $disk['use_path_style_endpoint'] ?? null,
            'app_environment' => config('app.env'),
        ];
    }
}
