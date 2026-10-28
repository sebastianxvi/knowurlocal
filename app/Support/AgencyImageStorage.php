<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Stores public agency images and verifies the object actually exists.
 * Existing database paths and public URL generation remain unchanged.
 */
final class AgencyImageStorage
{
    public static function store(UploadedFile $file): string
    {
        $diskName = 'public';
        $diskConfig = (array) config("filesystems.disks.{$diskName}", []);
        $disk = Storage::disk($diskName);
        $diagnostic = [
            'operation' => 'agency_image_upload',
            'disk' => $diskName,
            'driver' => $diskConfig['driver'] ?? null,
            'bucket' => $diskConfig['bucket'] ?? null,
            'root_prefix' => $diskConfig['root'] ?? null,
            'endpoint_host' => isset($diskConfig['endpoint'])
                ? (parse_url((string) $diskConfig['endpoint'], PHP_URL_HOST) ?: null)
                : null,
            'file_size_bytes' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'extension' => $file->guessExtension(),
            'configuration_cached' => app()->configurationIsCached(),
        ];

        Log::info('Agency image upload started.', $diagnostic);

        try {
            if (!$file->isValid()) {
                throw new RuntimeException('The uploaded agency image is not valid.');
            }

            $path = $file->store('agencies', $diskName);
            if (!is_string($path) || trim($path) === '') {
                throw new RuntimeException('The storage disk did not return an object path.');
            }

            if (!$disk->exists($path)) {
                Log::error('Agency image upload returned a path but the object was not found after upload.', $diagnostic + [
                    'object_path' => $path,
                ]);
                throw new RuntimeException('Agency image storage verification failed.');
            }

            Log::info('Agency image upload verified.', $diagnostic + [
                'object_path' => $path,
            ]);

            return $path;
        } catch (Throwable $exception) {
            Log::error('Agency image upload failed.', $diagnostic + [
                'exception_class' => get_class($exception),
                // Storage exceptions can contain provider details; do not log credentials/config values.
                'error' => mb_substr($exception->getMessage(), 0, 500),
            ]);

            throw $exception;
        }
    }
}
