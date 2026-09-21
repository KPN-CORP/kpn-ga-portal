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
        // Pakai get() bukan first(): satu path foto sekarang bisa direferensikan oleh
        // lebih dari satu TripLog (photo_after trip lama dipinjam jadi photo_before
        // trip baru untuk driver berikutnya), jadi semua kemungkinan log harus dicek.
        $logs = \App\Models\Drms\TripLog::where('photo_before', $path)
            ->orWhere('photo_after', $path)
            ->orWhere('photo_fuel_receipt', $path)
            ->get();
        foreach ($logs as $log) {
            $requestBu = $log->request->current_business_unit_id ?? $log->request->requester->drmsProfile->business_unit_id ?? null;
            if ($requestBu == $buId) return;
        }

        $fuelLog = \App\Models\Drms\FuelLog::where('receipt_file', $path)->first();
        if ($fuelLog && $fuelLog->vehicle && $fuelLog->vehicle->business_unit_id == $buId) return;

        $service = \App\Models\Drms\ServiceSchedule::where('invoice_file', $path)->first();
        if ($service && $service->vehicle && $service->vehicle->business_unit_id == $buId) return;

        $repair = \App\Models\Drms\Repair::where('invoice_file', $path)->first();
        if ($repair && $repair->vehicle && $repair->vehicle->business_unit_id == $buId) return;

        abort(403);
    }

    private function checkImageBelongsToDriver($path, $driverId)
    {
        // Sama seperti di atas: cek semua log yang mereferensikan path ini, karena
        // foto "sesudah" trip sebelumnya bisa dipakai/dipinjam sebagai foto "sebelum"
        // oleh driver lain di trip berikutnya untuk kendaraan yang sama.
        $logs = \App\Models\Drms\TripLog::where('photo_before', $path)
            ->orWhere('photo_after', $path)
            ->orWhere('photo_fuel_receipt', $path)
            ->get();
        foreach ($logs as $log) {
            if ($log->request && $log->request->driver_id == $driverId) return;
        }

        $fuelLog = \App\Models\Drms\FuelLog::where('receipt_file', $path)->first();
        if ($fuelLog && $fuelLog->driver_id == $driverId) return;

        abort(403);
    }
}