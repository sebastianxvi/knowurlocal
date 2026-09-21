<?php

namespace App\Services;

use App\Models\SupportRequest;
use App\Models\SupportRequestResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
                        $content = $component['file']->store(
                            'support-responses',
                            'private'
                        );
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
                ]);

                return $response->load('components');
            } catch (\Throwable $exception) {

                /*
                 * If a file was successfully stored but a later
                 * database operation fails, remove the stored
                 * files before allowing the exception to propagate.
                 */
                foreach ($response->components as $component) {
                    if (
                        in_array($component->type, ['image', 'file'], true)
                        && $component->content
                    ) {
                        Storage::disk('private')->delete(
                            $component->content
                        );
                    }
                }

                throw $exception;
            }
        });
    }
}