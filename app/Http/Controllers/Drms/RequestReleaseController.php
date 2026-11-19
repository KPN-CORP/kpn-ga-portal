<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\DriverRequest;
use App\Models\Drms\RequestRelease;
use App\Services\Drms\SlotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * "Selesai hari ini" untuk trip multi-hari: dipakai driver (trip miliknya) dan admin (BU/area-nya).
 */
class RequestReleaseController extends Controller
{
    public function store(Request $request, DriverRequest $driverRequest)
    {
        $role = $this->actorRole($driverRequest);

        $data = $request->validate([
            'free_from'   => ['required', 'date_format:H:i'],
            'resume_time' => ['nullable', 'date_format:H:i'],
            'note'        => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $release = SlotService::createRelease(
                $driverRequest,
                Auth::user(),
                $role,
                $data['free_from'],
                $data['resume_time'] ?? null,
                $request->boolean('vehicle_released'),
                $request->boolean('at_home_city'),
                $data['note'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors($e->getMessage());
        }

        return back()->with('success', 'Slot dilepas: bebas ' . $release->free_from->format('H:i')
            . ', trip lanjut ' . $release->free_until->format('d M H:i') . '.');
    }

    public function cancel(RequestRelease $release)
    {
        $driverRequest = $release->request;
        $this->actorRole($driverRequest);

        if ($release->cancelled_at) {
            return back()->withErrors('Slot ini sudah dibatalkan.');
        }

        $affected = SlotService::cancelRelease($release, Auth::user());

        return back()->with('success', 'Slot dibatalkan.' . ($affected
            ? ' Request ' . implode(', ', $affected) . ' berada di dalam slot itu; admin sudah diberi notifikasi untuk menugaskan ulang.'
            : ''));
    }

    /** 'driver' kalau driver trip ini, 'admin' kalau admin yang berwenang; selain itu 403. */
    private function actorRole(DriverRequest $driverRequest): string
    {
        $user = Auth::user();

        $driver = $user->driver;
        if ($driver && (int) $driverRequest->driver_id === (int) $driver->id) {
            return 'driver';
        }

        if ($user->isDrmsAdmin() || $user->isDrmsSuperAdmin()) {
            if (!$user->isDrmsSuperAdmin()) {
                $this->authorizeAdmin($driverRequest);
            }

            return 'admin';
        }

        abort(403, 'Anda tidak memiliki akses ke perjalanan ini.');
    }

    /** Sama dengan AppAdminController::authorizeAdminAccess (BU dan area admin). */
    private function authorizeAdmin(DriverRequest $driverRequest): void
    {
        $adminProfile = Auth::user()->drmsProfile;
        if (!$adminProfile) {
            abort(403, 'Profil admin tidak ditemukan.');
        }

        $requesterBu = $driverRequest->requester->drmsProfile->business_unit_id ?? null;
        $currentBu = $driverRequest->current_business_unit_id ?? $requesterBu;
        if ($adminProfile->business_unit_id != $currentBu) {
            abort(403, 'Anda tidak memiliki akses ke permintaan ini.');
        }

        if (!empty($adminProfile->adminAreaList()) && empty($driverRequest->current_business_unit_id)) {
            $requesterArea = $driverRequest->requester->drmsProfile->area ?? null;
            if (!$adminProfile->holdsArea($requesterArea)) {
                abort(403, 'Permintaan ini milik area lain.');
            }
        }
    }
}
