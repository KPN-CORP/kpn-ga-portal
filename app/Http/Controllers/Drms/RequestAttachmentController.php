<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\DriverRequest;
use App\Models\Drms\RequestAttachment;
use Illuminate\Support\Facades\Auth;

/**
 * Menampilkan lampiran permintaan (drms/requests).
 * Boleh dilihat oleh: pemohon, atasan L1 request tsb, admin DRMS (BU terkait), superadmin.
 *
 * Sengaja TIDAK lewat ImageController (private.image) karena route itu hanya
 * mengizinkan superadmin/admin/driver, sehingga pemohon & atasan L1 akan kena 403.
 */
class RequestAttachmentController extends Controller
{
    public function show(DriverRequest $driverRequest, $attachmentId)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $attachment = RequestAttachment::where('request_id', $driverRequest->id)->findOrFail($attachmentId);

        $this->authorizeView($user, $driverRequest);

        $fullPath = storage_path('app/private/' . $attachment->file_path);
        if (!is_file($fullPath)) {
            abort(404);
        }

        $mime = mime_content_type($fullPath) ?: ($attachment->mime_type ?: 'application/octet-stream');
        $name = str_replace(['"', "\r", "\n"], '', $attachment->original_name ?: basename($fullPath));

        return response()->file($fullPath, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . $name . '"',
            'Cache-Control'       => 'private, max-age=3600',
        ]);
    }

    private function authorizeView($user, DriverRequest $req): void
    {
        if ($req->requester_id === $user->id || $req->approver_l1_id === $user->id) {
            return;
        }

        if ($user->isDrmsSuperAdmin()) {
            return;
        }

        if ($user->isDrmsAdmin()) {
            $buId = optional($user->drmsProfile)->business_unit_id;
            $requesterBu = optional(optional($req->requester)->drmsProfile)->business_unit_id;
            $allowedBus = array_filter([$req->current_business_unit_id, $req->original_business_unit_id, $requesterBu]);

            if ($buId && in_array($buId, $allowedBus)) {
                return;
            }
        }

        abort(403);
    }
}
