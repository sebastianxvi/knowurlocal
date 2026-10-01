<?php

namespace App\Services;

use App\Models\SupportRequest;
use App\Models\SupportRequestResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Support\PrivateStorageDiagnostics;

class SupportRequestResponseService
{
    /**
     * Create and forward an official response.
     *
     * The entire operation is performed inside one
     * database transaction so the ticket cannot be
     * left partially updated if something fails.
     */
    public function createAndForward(
        SupportRequest $supportRequest,
        int $adminId,
        int $agencyId,
        array $components
    ): SupportRequestResponse {
        return DB::transaction(function () use (
            $supportRequest,
            $adminId,
            $agencyId,
            $components
        ) {
            /*
            * Persist the agency selected by the administrator.
            *
            * This keeps the Support Request associated with the
            * agency responsible for handling it.
            */
            $supportRequest->update([
                'agency_id' => $agencyId,
            ]);
            
            $response = SupportRequestResponse::create([
                'support_request_id' => $supportRequest->id,
                'admin_id' => $adminId,
                'status' => 'forwarded',
                'forwarded_at' => now(),
            ]);

            // Track uploaded paths in memory. Once PostgreSQL marks a
            // transaction as failed, querying $response->components from
            // inside the catch block would fail too and hide the original
            // database exception (SQLSTATE 25P02).
            $storedFilePaths = [];

            try {
                foreach ($components as $index => $component) {
                    $content = $component['content'] ?? null;

                    /*
                     * Uploaded files are stored using Laravel's
                     * filesystem abstraction rather than trusting
                     * the original filename.
                     */
                    if (
                        in_array($component['type'], ['image', 'file'], true)
                        && isset($component['file'])
                        && $component['file'] instanceof UploadedFile
                    ) {
                        $upload = $component['file'];
                        $diagnostic = PrivateStorageDiagnostics::context() + [
                            'feature' => 'support_response_attachment',
                            'component_index' => $index,
                            'file_size_bytes' => $upload->getSize(),
                            'file_mime_type' => $upload->getMimeType(),
                        ];

                        Log::info('Private storage upload starting.', $diagnostic);

                        try {
                            $content = $upload->store('support-responses', 'private');
                        } catch (\Throwable $exception) {
                            Log::error('Private storage upload failed.', $diagnostic + [
                                'exception_class' => get_class($exception),
                                'exception_message' => mb_substr($exception->getMessage(), 0, 700),
                            ]);

                            throw $exception;
                        }

                        if (is_string($content) && $content !== '') {
                            $storedFilePaths[] = $content;
                        }
                    }

                    $response->components()->create([
                        'type' => $component['type'],
                        'content' => $content,
                        'label' => $component['label'] ?? null,
                        'sort_order' => $index,
                    ]);
                }

                /*
                 * The citizen now has an official response
                 * waiting for confirmation.
                 */
                $supportRequest->update([
                    'status' => 'awaiting_confirmation',
                    'answer_seen_at' => null,
                ]);

                return $response->load('components');
            } catch (\Throwable $exception) {

                /*
                 * If a file was successfully stored but a later
                 * database operation fails, remove the stored
                 * files before allowing the exception to propagate.
                 */
                // Do not query the database here: PostgreSQL may already
                // have aborted the transaction, which would mask the
                // original exception with SQLSTATE 25P02.
                foreach ($storedFilePaths as $storedFilePath) {
                    Storage::disk('private')->delete($storedFilePath);
                }

                throw $exception;
            }
        });
    }
}