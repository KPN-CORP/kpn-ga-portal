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

class AppAdminController extends Controller
{
    /**
     * Tampilkan daftar request yang sudah disetujui atasan dan menunggu proses admin.
     */
    public function index()
    {
        $user = Auth::user();
        $businessUnitId = $user->business_unit_id;
        $area = $user->area;

        $pendingRequests = DriverRequest::with('requester')
            ->pendingAdmin($businessUnitId, $area)
            ->latest()
            ->get();

        // Ambil history request yang sudah diproses oleh admin ini
        $historyRequests = DriverRequest::with(['requester', 'approverL1', 'admin', 'driver', 'vehicle', 'voucher'])
            ->where('admin_id', $user->id)
            ->whereIn('status', ['approved_admin', 'rejected_admin'])
            ->latest()
            ->paginate(10);

        return view('drms.approval.admin.index', compact('pendingRequests', 'historyRequests'));
    }

    /**
     * Form untuk menentukan driver/voucher.
     */
    public function edit($id)
    {
        $driverRequest = DriverRequest::with('requester.drmsProfile')->findOrFail($id);
        $user = Auth::user();

        if (!$user->isDrmsSuperAdmin()) {
            if ($driverRequest->requester->business_unit_id != $user->business_unit_id ||
                $driverRequest->requester->area != $user->area) {
                abort(403);
            }
        }

        $drivers = Driver::where('business_unit_id', $user->business_unit_id)->get();
        $vehicles = Vehicle::where('business_unit_id', $user->business_unit_id)->get();
        $vouchers = Voucher::where('business_unit_id', $user->business_unit_id)
            ->where('status', 'available')
            ->get();

        return view('drms.approval.admin.edit', compact('driverRequest', 'drivers', 'vehicles', 'vouchers'));
    }

    /**
     * Proses penentuan transportasi.
     */
    public function update(Request $request, $id)
    {
        $driverRequest = DriverRequest::findOrFail($id);
        $user = Auth::user();

        // Cek akses
        if (!$user->isDrmsSuperAdmin()) {
            if ($driverRequest->requester->business_unit_id != $user->business_unit_id ||
                $driverRequest->requester->area != $user->area) {
                abort(403);
            }
        }

        $data = $request->validate([
            'transport_type' => 'required|in:company_driver,voucher,rental',
            'driver_id' => 'nullable|required_if:transport_type,company_driver|exists:drms_drivers,id',
            'vehicle_id' => 'nullable|required_if:transport_type,company_driver|exists:drms_vehicles,id',
            'voucher_id' => 'nullable|required_if:transport_type,voucher|exists:drms_vouchers,id',
            'keterangan' => 'nullable|string',
        ]);

        // Cek double booking jika memilih driver & vehicle
        if ($data['transport_type'] === 'company_driver') {
            // Pastikan end_time ada
            if (!$driverRequest->end_time) {
                return back()->withErrors('Request ini tidak memiliki perkiraan jam selesai. Harap edit request terlebih dahulu atau hubungi pembuat.');
            }

            // Cek driver
            $driverConflict = DriverRequest::overlapping(
                'driver_id',
                $data['driver_id'],
                $driverRequest->usage_date->format('Y-m-d'),
                $driverRequest->start_time,
                $driverRequest->end_time,
                $driverRequest->id
            )->exists();
            if ($driverConflict) {
                return back()->withErrors('Driver sudah ditugaskan pada rentang waktu tersebut.');
            }

            // Cek kendaraan
            $vehicleConflict = DriverRequest::overlapping(
                'vehicle_id',
                $data['vehicle_id'],
                $driverRequest->usage_date->format('Y-m-d'),
                $driverRequest->start_time,
                $driverRequest->end_time,
                $driverRequest->id
            )->exists();
            if ($vehicleConflict) {
                return back()->withErrors('Kendaraan sudah digunakan pada rentang waktu tersebut.');
            }
        }

        // Update request
        $driverRequest->update([
            'transport_type' => $data['transport_type'],
            'driver_id' => $data['driver_id'] ?? null,
            'vehicle_id' => $data['vehicle_id'] ?? null,
            'voucher_id' => $data['voucher_id'] ?? null,
            'admin_id' => Auth::id(),
            'status' => 'approved_admin',
            'approved_admin_at' => now(),
            'rejection_reason' => $data['keterangan'] ?? null,
        ]);

        // Update status driver/vehicle/voucher
        if (!empty($data['driver_id'])) {
            Driver::where('id', $data['driver_id'])->update(['status' => 'on_trip']);
        }
        if (!empty($data['vehicle_id'])) {
            Vehicle::where('id', $data['vehicle_id'])->update(['status' => 'in_use']);
        }
        if (!empty($data['voucher_id'])) {
            Voucher::where('id', $data['voucher_id'])->update(['status' => 'used']);
        }

        // Notifikasi ke requester
        $driverRequest->requester->notify(new RequestApprovedAdminNotification($driverRequest));

        return redirect()->route('drms.approval.admin.index')
            ->with('success', 'Request berhasil diproses.');
    }

    /**
     * Tampilkan history approval admin (opsional, sudah di index).
     */
    public function history()
    {
        $user = Auth::user();
        $historyRequests = DriverRequest::with('requester')
            ->where('admin_id', $user->id)
            ->whereIn('status', ['approved_admin', 'rejected_admin'])
            ->latest()
            ->paginate(10);

        return view('drms.approval.admin.history', compact('historyRequests'));
    }
}