<?php

namespace App\Http\Controllers\HSRM;

use App\Http\Controllers\Controller;
use App\Models\HSRM\HsrmCertificate;
use App\Models\HSRM\HsrmEquipment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class HsrmFileController extends Controller
{
    public function download($type, $id, $old_index = null)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }

        if ($old_index === null) {
            $old_index = request('old_index');
        }

        if ($type === 'certificate') {
            $item = HsrmCertificate::with('area')->findOrFail($id);
            $this->authorizeView($item);
            $path = $this->getPath($item, $old_index);
        } elseif ($type === 'equipment') {
            $item = HsrmEquipment::with('area')->findOrFail($id);
            $this->authorizeView($item);
            $path = $this->getPath($item, $old_index);
        } else {
            abort(404, 'Invalid type');
        }

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'File not found');
        }

        $fullPath = Storage::disk('public')->path($path);
        if (!is_readable($fullPath)) {
            abort(500, 'File is not readable.');
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeTypes = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
        ];
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

        if ($extension === 'pdf') {
            $content = file_get_contents($fullPath);
            return view('hsrm.file_viewer', [
                'content' => base64_encode($content),
                'mime' => $mimeType,
                'filename' => basename($path),
            ]);
        }

        return response()->stream(function () use ($fullPath) {
            readfile($fullPath);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function getPath($item, $old_index = null)
    {
        if ($old_index !== null && isset($item->old_attachments[$old_index])) {
            $old = $item->old_attachments[$old_index];
            return $old['path'] ?? null;
        }
        return $item->attachment_path ?? $item->photo_path ?? null;
    }

    private function authorizeView($item)
    {
        $user = Auth::user();
        if (session('hsrm_role') === 'admin') {
            return;
        }
        $areaIds = $user->hsrmAreas->pluck('id_area_kerja')->toArray();
        if (!in_array($item->area_id, $areaIds)) {
            abort(403, 'You do not have access to this file.');
        }
    }
}