<?php

namespace App\Support;

/**
 * Safe, temporary diagnostics for the private storage disk.
 * Never returns or logs credential values.
 */
final class PrivateStorageDiagnostics
{
    public static function context(): array
    {
        $disk = (array) config('filesystems.disks.private', []);
        $endpoint = $disk['endpoint'] ?? null;
        $endpointParts = is_string($endpoint) ? parse_url($endpoint) : false;
        $key = $disk['key'] ?? null;
        $secret = $disk['secret'] ?? null;

        return [
            'storage_disk' => 'private',
            'storage_driver' => $disk['driver'] ?? null,
            'access_key_present' => is_string($key) && trim($key) !== '',
            'secret_key_present' => is_string($secret) && trim($secret) !== '',
            'region' => $disk['region'] ?? null,
            'bucket' => $disk['bucket'] ?? null,
            'endpoint_host' => is_array($endpointParts) ? ($endpointParts['host'] ?? null) : null,
            'path_style_endpoint' => $disk['use_path_style_endpoint'] ?? null,
            'app_environment' => config('app.env'),
        ];
    }
}
