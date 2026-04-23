<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\Driver;
use App\Models\Drms\DriverRequest;
use App\Models\Drms\Vehicle;
use App\Models\Drms\Voucher;
use App\Notifications\RequestApprovedAdminNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppAdminController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $profile = $user->drmsProfile;
        if (!$profile) {
            abort(403, 'Profil DRMS tidak ditemukan.');
        }

        $businessUnitId = $profile->business_unit_id;
        $area = $profile->area;

        $pendingRequests = DriverRequest::with('requester')
            ->pendingAdmin($businessUnitId, $area)
            ->latest()
            ->get();

        $historyRequests = DriverRequest::with(['requester', 'approverL1', 'admin', 'driver', 'vehicle', 'voucher'])
            ->where('admin_id', $user->id)
            ->whereIn('status', ['approved_admin', 'rejected_admin'])
            ->latest()
            ->paginate(10);

        return view('drms.approval.admin.index', compact('pendingRequests', 'historyRequests'));
    }

    public function edit($id)
    {
        $driverRequest = DriverRequest::with('requester.drmsProfile')->findOrFail($id);
        $user = Auth::user();

        if (!$user->isDrmsSuperAdmin()) {
            $requesterProfile = $driverRequest->requester->drmsProfile;
            $adminProfile = $user->drmsProfile;

            if (!$adminProfile ||
                $requesterProfile->business_unit_id != $adminProfile->business_unit_id ||
                $requesterProfile->area != $adminProfile->area) {
                abort(403);
            }
        }

        $drivers = Driver::where('business_unit_id', $user->drmsProfile->business_unit_id)->get();
        $vehicles = Vehicle::where('business_unit_id', $user->drmsProfile->business_unit_id)->get();
        $vouchers = Voucher::where('business_unit_id', $user->drmsProfile->business_unit_id)
            ->where('status', 'available')
            ->get();

        return view('drms.approval.admin.edit', compact('driverRequest', 'drivers', 'vehicles', 'vouchers'));
    }

    public function update(Request $request, $id)
    {
        $driverRequest = DriverRequest::findOrFail($id);
        $user = Auth::user();

        // Otorisasi
        if (!$user->isDrmsSuperAdmin()) {
            $requesterProfile = $driverRequest->requester->drmsProfile;
            $adminProfile = $user->drmsProfile;

            if (!$adminProfile ||
                $requesterProfile->business_unit_id != $adminProfile->business_unit_id ||
                $requesterProfile->area != $adminProfile->area) {
                abort(403);
            }
        }

        $data = $request->validate([
            'transport_type' => 'required|in:company_driver,voucher,rental',
            'driver_id'      => 'nullable|required_if:transport_type,company_driver|exists:drms_drivers,id',
            'vehicle_id'     => 'nullable|required_if:transport_type,company_driver|exists:drms_vehicles,id',
            'voucher_id'     => 'nullable|required_if:transport_type,voucher|exists:drms_vouchers,id',
            'keterangan'     => 'nullable|string',
        ]);

        if ($data['transport_type'] === 'company_driver') {
            if (!$driverRequest->end_time) {
                return back()->withErrors('Request ini tidak memiliki perkiraan jam selesai.');
            }

            $startDate = $driverRequest->usage_date->format('Y-m-d');
            $startTime = $driverRequest->start_time;
            if ($driverRequest->trip_type === 'round_trip' && $driverRequest->return_date) {
                $endDate = $driverRequest->return_date->format('Y-m-d');
                $endTime = $driverRequest->return_time ?? $driverRequest->end_time;
            } else {
                $endDate = $startDate;
                $endTime = $driverRequest->end_time;
            }

            // Cek konflik driver
            $driverConflict = DriverRequest::overlappingPeriod(
                'driver_id',
                $data['driver_id'],
                $startDate,
                $startTime,
                $endDate,
                $endTime,
                $driverRequest->id
            )->exists();

            if ($driverConflict) {
                return back()->withErrors('Driver sudah ditugaskan pada rentang waktu tersebut.');
            }

            // Cek konflik kendaraan
            $vehicleConflict = DriverRequest::overlappingPeriod(
                'vehicle_id',
                $data['vehicle_id'],
                $startDate,
                $startTime,
                $endDate,
                $endTime,
                $driverRequest->id
            )->exists();

            if ($vehicleConflict) {
                return back()->withErrors('Kendaraan sudah digunakan pada rentang waktu tersebut.');
            }
        }

        DB::beginTransaction();
        try {
            $driverRequest->update([
                'transport_type'    => $data['transport_type'],
                'driver_id'         => $data['driver_id'] ?? null,
                'vehicle_id'        => $data['vehicle_id'] ?? null,
                'voucher_id'        => $data['voucher_id'] ?? null,
                'admin_id'          => Auth::id(),
                'status'            => 'approved_admin',
                'approved_admin_at' => now(),
                'rejection_reason'  => $data['keterangan'] ?? null,
            ]);

            // Update status resource terkait
            if (!empty($data['driver_id'])) {
                Driver::where('id', $data['driver_id'])->update(['status' => 'on_trip']);
            }
            if (!empty($data['vehicle_id'])) {
                Vehicle::where('id', $data['vehicle_id'])->update(['status' => 'in_use']);
            }
            if (!empty($data['voucher_id'])) {
                Voucher::where('id', $data['voucher_id'])->update(['status' => 'used']);
            }

            $driverRequest->requester->notify(new RequestApprovedAdminNotification($driverRequest));

            DB::commit();
            return redirect()->route('drms.approval.admin.index')
                ->with('success', 'Request berhasil diproses.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $driverRequest = DriverRequest::findOrFail($id);
        $user = Auth::user();

        // Otorisasi (sama seperti di update)
        if (!$user->isDrmsSuperAdmin()) {
            $requesterProfile = $driverRequest->requester->drmsProfile;
            $adminProfile = $user->drmsProfile;

            if (!$adminProfile ||
                $requesterProfile->business_unit_id != $adminProfile->business_unit_id ||
                $requesterProfile->area != $adminProfile->area) {
                abort(403);
            }
        }

        if ($driverRequest->status !== 'approved_l1') {
            return back()->withErrors('Permintaan tidak dapat ditolak karena status sudah diproses.');
        }

        $driverRequest->update([
            'status'           => 'rejected_admin',
            'rejection_reason' => $request->rejection_reason,
            'admin_id'         => Auth::id(),
            'approved_admin_at'=> null,
            'transport_type'   => null,
            'driver_id'        => null,
            'vehicle_id'       => null,
            'voucher_id'       => null,
        ]);

        return redirect()->route('drms.approval.admin.index')
            ->with('success', 'Permintaan driver ditolak.');
    }

    // history() method tetap sama...
}