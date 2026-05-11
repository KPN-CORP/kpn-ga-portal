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
use Illuminate\Support\Facades\Response;

class AppAdminController extends Controller
{
    /**
     * Menampilkan halaman approval admin dengan filter pencarian, status, dan tanggal.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $profile = $user->drmsProfile;

        // Superadmin bisa akses semua tanpa profil DRMS
        if (!$user->isDrmsSuperAdmin() && !$profile) {
            abort(403, 'Profil DRMS tidak ditemukan.');
        }

        $businessUnitId = $profile->business_unit_id ?? null;
        $area = $profile->area ?? null;

        // ------------------- PENDING REQUESTS -------------------
        if ($user->isDrmsSuperAdmin()) {
            $pendingRequests = DriverRequest::with('requester')
                ->where('status', 'approved_l1')
                ->latest()
                ->get();
        } else {
            $pendingRequests = DriverRequest::with('requester')
                ->pendingAdmin($businessUnitId, $area)
                ->latest()
                ->get();
        }

        // ------------------- HISTORY REQUESTS (dengan filter) -------------------
        $historyQuery = DriverRequest::with(['requester', 'approverL1', 'admin', 'driver', 'vehicle', 'voucher']);

        if ($user->isDrmsSuperAdmin()) {
            $historyQuery->whereIn('status', ['approved_admin', 'rejected_admin', 'completed']);
        } else {
            $historyQuery->where('admin_id', $user->id)
                ->whereIn('status', ['approved_admin', 'rejected_admin', 'completed']);
        }

        // Filter status
        if ($request->filled('status') && in_array($request->status, ['approved_admin', 'rejected_admin', 'completed'])) {
            $historyQuery->where('status', $request->status);
        }

        // Filter pencarian (request_no, pemohon, lokasi, tujuan, keperluan)
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $historyQuery->where(function ($q) use ($searchTerm) {
                $q->where('request_no', 'LIKE', $searchTerm)
                  ->orWhere('pickup_location', 'LIKE', $searchTerm)
                  ->orWhere('destination', 'LIKE', $searchTerm)
                  ->orWhere('purpose', 'LIKE', $searchTerm)
                  ->orWhereHas('requester', function ($q2) use ($searchTerm) {
                      $q2->where('name', 'LIKE', $searchTerm);
                  });
            });
        }

        // Filter rentang tanggal (usage_date)
        if ($request->filled('date_from')) {
            $historyQuery->whereDate('usage_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $historyQuery->whereDate('usage_date', '<=', $request->date_to);
        }

        $historyRequests = $historyQuery->latest()->paginate(10)->appends($request->query());

        return view('drms.approval.admin.index', compact('pendingRequests', 'historyRequests'));
    }

    /**
     * Export data history ke CSV berdasarkan filter yang sedang aktif.
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $profile = $user->drmsProfile;

        // Bangun query sama seperti di index (tanpa pagination)
        $exportQuery = DriverRequest::with(['requester', 'approverL1', 'admin', 'driver', 'vehicle', 'voucher']);

        if ($user->isDrmsSuperAdmin()) {
            $exportQuery->whereIn('status', ['approved_admin', 'rejected_admin', 'completed']);
        } else {
            $exportQuery->where('admin_id', $user->id)
                ->whereIn('status', ['approved_admin', 'rejected_admin', 'completed']);
        }

        // Filter status
        if ($request->filled('status') && in_array($request->status, ['approved_admin', 'rejected_admin', 'completed'])) {
            $exportQuery->where('status', $request->status);
        }

        // Filter pencarian
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $exportQuery->where(function ($q) use ($searchTerm) {
                $q->where('request_no', 'LIKE', $searchTerm)
                  ->orWhere('pickup_location', 'LIKE', $searchTerm)
                  ->orWhere('destination', 'LIKE', $searchTerm)
                  ->orWhere('purpose', 'LIKE', $searchTerm)
                  ->orWhereHas('requester', function ($q2) use ($searchTerm) {
                      $q2->where('name', 'LIKE', $searchTerm);
                  });
            });
        }

        // Filter tanggal
        if ($request->filled('date_from')) {
            $exportQuery->whereDate('usage_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $exportQuery->whereDate('usage_date', '<=', $request->date_to);
        }

        $requests = $exportQuery->latest()->get();

        // Buat CSV
        $filename = 'laporan_approval_admin_' . date('Ymd_His') . '.csv';
        $handle = fopen('php://temp', 'w+');

        // Header CSV
        fputcsv($handle, [
            'No. Request',
            'Pemohon',
            'Tanggal Penggunaan',
            'Jam Mulai',
            'Jam Selesai',
            'Tipe Perjalanan',
            'Tanggal Kembali',
            'Lokasi Jemput',
            'Tujuan',
            'Keperluan',
            'Jenis Transportasi',
            'Driver',
            'Kendaraan',
            'Voucher',
            'Status',
            'Disetujui Atasan (Tanggal)',
            'Diproses GA (Tanggal)',
            'Alasan Penolakan'
        ]);

        foreach ($requests as $req) {
            fputcsv($handle, [
                $req->request_no,
                $req->requester->name ?? '-',
                $req->usage_date ? $req->usage_date->format('d-m-Y') : '-',
                $req->start_time,
                $req->end_time,
                $req->trip_type === 'round_trip' ? 'Pulang Pergi' : 'Sekali Jalan',
                $req->return_date ? $req->return_date->format('d-m-Y') : '-',
                $req->pickup_location,
                $req->destination,
                $req->purpose,
                $req->transport_type ? ucfirst(str_replace('_', ' ', $req->transport_type)) : '-',
                $req->driver->name ?? '-',
                $req->vehicle->plate_number ?? '-',
                $req->voucher->code ?? '-',
                $req->status === 'approved_admin' ? 'Disetujui' : ($req->status === 'rejected_admin' ? 'Ditolak' : 'Selesai'),
                $req->approved_l1_at ? $req->approved_l1_at->format('d-m-Y H:i') : '-',
                $req->approved_admin_at ? $req->approved_admin_at->format('d-m-Y H:i') : '-',
                $req->rejection_reason ?? '-'
            ]);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        return Response::make($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    /**
     * Menampilkan form proses approval admin.
     */
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

        if ($user->isDrmsSuperAdmin()) {
            $drivers = Driver::where('status', 'available')->get();
            $vehicles = Vehicle::where('status', 'available')->get();
        } else {
            $drivers = Driver::where('business_unit_id', $user->drmsProfile->business_unit_id)
                ->where('status', 'available')
                ->get();
            $vehicles = Vehicle::where('business_unit_id', $user->drmsProfile->business_unit_id)
                ->where('status', 'available')
                ->get();
        }

        $vouchers = Voucher::where('business_unit_id', $user->drmsProfile->business_unit_id)
            ->where('status', 'available')
            ->get();

        return view('drms.approval.admin.edit', compact('driverRequest', 'drivers', 'vehicles', 'vouchers'));
    }

    /**
     * Memproses approval admin (setujui).
     */
    public function update(Request $request, $id)
    {
        $driverRequest = DriverRequest::findOrFail($id);
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

        $data = $request->validate([
            'transport_type' => 'required|in:company_driver,voucher,rental',
            'driver_id'      => 'nullable|required_if:transport_type,company_driver|exists:drms_drivers,id',
            'vehicle_id'     => 'nullable|required_if:transport_type,company_driver|exists:drms_vehicles,id',
            'voucher_id'     => 'nullable|required_if:transport_type,voucher|exists:drms_vouchers,id',
            'keterangan'     => 'nullable|string',
        ]);

        $oldDriverId  = $driverRequest->driver_id;
        $oldVehicleId = $driverRequest->vehicle_id;
        $oldVoucherId = $driverRequest->voucher_id;

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

            if ($oldDriverId && $oldDriverId != ($data['driver_id'] ?? null)) {
                Driver::where('id', $oldDriverId)->update(['status' => 'available']);
            }
            if ($oldVehicleId && $oldVehicleId != ($data['vehicle_id'] ?? null)) {
                Vehicle::where('id', $oldVehicleId)->update(['status' => 'available']);
            }
            if ($oldVoucherId && $oldVoucherId != ($data['voucher_id'] ?? null)) {
                Voucher::where('id', $oldVoucherId)->update(['status' => 'available']);
            }

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

    /**
     * Menolak permintaan oleh admin.
     */
    public function reject(Request $request, $id)
    {
        $request->validate(['rejection_reason' => 'required|string']);

        $driverRequest = DriverRequest::findOrFail($id);
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

        if ($driverRequest->status !== 'approved_l1') {
            return back()->withErrors('Permintaan tidak dapat ditolak karena status sudah diproses.');
        }

        $oldDriverId  = $driverRequest->driver_id;
        $oldVehicleId = $driverRequest->vehicle_id;
        $oldVoucherId = $driverRequest->voucher_id;

        DB::beginTransaction();
        try {
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

            if ($oldDriverId) Driver::where('id', $oldDriverId)->update(['status' => 'available']);
            if ($oldVehicleId) Vehicle::where('id', $oldVehicleId)->update(['status' => 'available']);
            if ($oldVoucherId) Voucher::where('id', $oldVoucherId)->update(['status' => 'available']);

            DB::commit();
            return redirect()->route('drms.approval.admin.index')
                ->with('success', 'Permintaan driver ditolak.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Menandai permintaan sebagai selesai oleh admin.
     */
    public function complete(DriverRequest $driverRequest)
    {
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

        if ($driverRequest->status !== 'approved_admin') {
            return back()->withErrors('Hanya permintaan dengan status Disetujui yang bisa diselesaikan.');
        }

        DB::beginTransaction();
        try {
            $driverRequest->update(['status' => 'completed']);

            if ($driverRequest->driver_id) {
                Driver::where('id', $driverRequest->driver_id)->update(['status' => 'available']);
            }
            if ($driverRequest->vehicle_id) {
                Vehicle::where('id', $driverRequest->vehicle_id)->update(['status' => 'available']);
            }

            DB::commit();
            return redirect()->route('drms.approval.admin.index')
                ->with('success', 'Permintaan berhasil diselesaikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal menyelesaikan permintaan.');
        }
    }
}