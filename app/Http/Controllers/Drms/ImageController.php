<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ImageController extends Controller
{
    public function show($path)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Anda harus login.');
        }

        $this->authorizeImageAccess($user, $path);

        $fullPath = storage_path('app/private/' . $path);
        if (!file_exists($fullPath)) {
            abort(404);
        }

        $mime = mime_content_type($fullPath) ?: 'image/jpeg';
        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    private function authorizeImageAccess($user, $path)
    {
        if ($user->isDrmsSuperAdmin()) {
            return;
        }

        if ($user->isDrmsAdmin()) {
            $buId = $user->drmsProfile->business_unit_id;
            $this->checkImageInSameBu($path, $buId);
            return;
        }

        if ($user->driver) {
            $driverId = $user->driver->id;
            $this->checkImageBelongsToDriver($path, $driverId);
            return;
        }

        abort(403);
    }

    private function checkImageInSameBu($path, $buId)
    {
        $log = \App\Models\Drms\TripLog::where('photo_before', $path)
            ->orWhere('photo_after', $path)
            ->orWhere('photo_fuel_receipt', $path)
            ->first();
        if ($log) {
            $requestBu = $log->request->current_business_unit_id ?? $log->request->requester->drmsProfile->business_unit_id;
            if ($requestBu == $buId) return;
        }

        $fuelLog = \App\Models\Drms\FuelLog::where('receipt_file', $path)->first();
        if ($fuelLog && $fuelLog->vehicle && $fuelLog->vehicle->business_unit_id == $buId) return;

        $service = \App\Models\Drms\ServiceSchedule::where('invoice_file', $path)->first();
        if ($service && $service->vehicle && $service->vehicle->business_unit_id == $buId) return;

        abort(403);
    }

    private function checkImageBelongsToDriver($path, $driverId)
    {
        $log = \App\Models\Drms\TripLog::where('photo_before', $path)
            ->orWhere('photo_after', $path)
            ->orWhere('photo_fuel_receipt', $path)
            ->first();
        if ($log && $log->request->driver_id == $driverId) return;

        $fuelLog = \App\Models\Drms\FuelLog::where('receipt_file', $path)->first();
        if ($fuelLog && $fuelLog->driver_id == $driverId) return;

        abort(403);
    }
}