<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\Driver;
use App\Models\Drms\DriverRequest;
use App\Models\Drms\Vehicle;
use App\Models\Drms\Voucher;
use App\Models\BisnisUnit;
use App\Models\User;  // <-- IMPORTANT: tambahkan ini
use App\Notifications\RequestApprovedAdminNotification;
use App\Notifications\RequestForwardedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AdminHistoryExport;

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
            $pendingRequests = DriverRequest::with('requester', 'currentBusinessUnit')
                ->where('status', 'approved_l1')
                ->latest()
                ->get();
        } else {
            $pendingRequests = DriverRequest::with('requester', 'currentBusinessUnit')
                ->pendingForAdmin($businessUnitId, $area)
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

        // Ambil semua business unit untuk dropdown forward modal
        $businessUnits = BisnisUnit::orderBy('nama_bisnis_unit')->get();

        return view('drms.approval.admin.index', compact('pendingRequests', 'historyRequests', 'businessUnits'));
    }

    /**
     * Export data history ke Excel (.xlsx) hanya jika ada filter yang aktif.
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $profile = $user->drmsProfile;

        // Cek apakah ada filter yang aktif
        $hasFilter = $request->filled('search') 
            || $request->filled('status') 
            || $request->filled('date_from') 
            || $request->filled('date_to');

        if (!$hasFilter) {
            return redirect()->back()->withErrors('Harap terapkan filter terlebih dahulu sebelum download laporan.');
        }

        // Bangun query export sama seperti di index
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

        // Export ke Excel
        $filename = 'laporan_approval_admin_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new AdminHistoryExport($exportQuery), $filename);
    }

    /**
     * Menampilkan form proses approval admin.
     * Driver dan kendaraan yang sedang on_trip/in_use tetapi tidak bentrok jadwal akan ditampilkan.
     */
    public function edit($id)
    {
        $driverRequest = DriverRequest::with('requester.drmsProfile')->findOrFail($id);
        $user = Auth::user();

        if (!$user->isDrmsSuperAdmin()) {
            $this->authorizeAdminAccess($driverRequest);
        }

        // Tentukan business unit ID yang berlaku (current BU atau BU requester)
        $businessUnitId = $driverRequest->current_business_unit_id ?? $driverRequest->requester->drmsProfile->business_unit_id;

        // Driver: available atau on_trip yang tidak bentrok
        $drivers = Driver::where('business_unit_id', $businessUnitId)
            ->where(function ($q) use ($driverRequest) {
                $q->where('status', 'available')
                  ->orWhere(function ($sub) use ($driverRequest) {
                      $sub->where('status', 'on_trip')
                          ->whereNotExists(function ($exists) use ($driverRequest) {
                              $exists->select(DB::raw(1))
                                  ->from('drms_requests')
                                  ->whereColumn('drms_requests.driver_id', 'drms_drivers.id')
                                  ->whereIn('drms_requests.status', ['approved_admin', 'pending_l1', 'approved_l1'])
                                  ->where('drms_requests.id', '!=', $driverRequest->id)
                                  ->whereRaw("
                                      CONCAT(drms_requests.usage_date, ' ', drms_requests.start_time) < ?
                                      AND CONCAT(COALESCE(drms_requests.return_date, drms_requests.usage_date), ' ', COALESCE(drms_requests.return_time, drms_requests.end_time)) > ?
                                  ", [
                                      $driverRequest->return_date . ' ' . $driverRequest->end_time,
                                      $driverRequest->usage_date . ' ' . $driverRequest->start_time
                                  ]);
                          });
                  });
            })
            ->get();

        // Kendaraan: available atau in_use yang tidak bentrok
        $vehicles = Vehicle::where('business_unit_id', $businessUnitId)
            ->where(function ($q) use ($driverRequest) {
                $q->where('status', 'available')
                  ->orWhere(function ($sub) use ($driverRequest) {
                      $sub->where('status', 'in_use')
                          ->whereNotExists(function ($exists) use ($driverRequest) {
                              $exists->select(DB::raw(1))
                                  ->from('drms_requests')
                                  ->whereColumn('drms_requests.vehicle_id', 'drms_vehicles.id')
                                  ->whereIn('drms_requests.status', ['approved_admin', 'pending_l1', 'approved_l1'])
                                  ->where('drms_requests.id', '!=', $driverRequest->id)
                                  ->whereRaw("
                                      CONCAT(drms_requests.usage_date, ' ', drms_requests.start_time) < ?
                                      AND CONCAT(COALESCE(drms_requests.return_date, drms_requests.usage_date), ' ', COALESCE(drms_requests.return_time, drms_requests.end_time)) > ?
                                  ", [
                                      $driverRequest->return_date . ' ' . $driverRequest->end_time,
                                      $driverRequest->usage_date . ' ' . $driverRequest->start_time
                                  ]);
                          });
                  });
            })
            ->get();

        $vouchers = Voucher::where('business_unit_id', $businessUnitId)
            ->where('status', 'available')
            ->get();

        $allBusinessUnits = BisnisUnit::orderBy('nama_bisnis_unit')->get();

        return view('drms.approval.admin.edit', compact('driverRequest', 'drivers', 'vehicles', 'vouchers', 'allBusinessUnits'));
    }

    /**
     * Memproses approval admin (setujui).
     */
    public function update(Request $request, $id)
    {
        $driverRequest = DriverRequest::findOrFail($id);
        $user = Auth::user();

        if (!$user->isDrmsSuperAdmin()) {
            $this->authorizeAdminAccess($driverRequest);
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

            // Update status lama
            if ($oldDriverId && $oldDriverId != ($data['driver_id'] ?? null)) {
                Driver::where('id', $oldDriverId)->update(['status' => 'available']);
            }
            if ($oldVehicleId && $oldVehicleId != ($data['vehicle_id'] ?? null)) {
                Vehicle::where('id', $oldVehicleId)->update(['status' => 'available']);
            }
            if ($oldVoucherId && $oldVoucherId != ($data['voucher_id'] ?? null)) {
                Voucher::where('id', $oldVoucherId)->update(['status' => 'available']);
            }

            // Update status baru
            if (!empty($data['driver_id'])) {
                Driver::where('id', $data['driver_id'])->update(['status' => 'on_trip']);
            }
            if (!empty($data['vehicle_id'])) {
                Vehicle::where('id', $data['vehicle_id'])->update(['status' => 'in_use']);
            }
            if (!empty($data['voucher_id'])) {
                Voucher::where('id', $data['voucher_id'])->update(['status' => 'used']);
            }

            // Kirim notifikasi ke pemohon
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
            $this->authorizeAdminAccess($driverRequest);
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
            $this->authorizeAdminAccess($driverRequest);
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

    /**
     * Mengalihkan permintaan ke business unit lain.
     */
    public function forward(Request $request, $id)
    {
        $driverRequest = DriverRequest::findOrFail($id);
        $user = Auth::user();

        if (!$user->isDrmsSuperAdmin()) {
            $this->authorizeAdminAccess($driverRequest);
        }

        if ($driverRequest->status !== 'approved_l1') {
            return back()->withErrors('Hanya permintaan dengan status approved_l1 yang bisa dialihkan.');
        }

        $request->validate([
            'target_business_unit_id' => 'required|exists:tb_bisnis_unit,id_bisnis_unit',
            'note' => 'nullable|string|max:500',
        ]);

        $targetBuId = $request->target_business_unit_id;

        // Cek apakah target BU memiliki admin aktif
        $targetAdmins = User::whereHas('drmsProfile', function ($q) use ($targetBuId) {
            $q->where('is_drms_admin', true)
              ->where('business_unit_id', $targetBuId);
        })->get();

        if ($targetAdmins->isEmpty()) {
            return back()->withErrors('Target Business Unit tidak memiliki admin aktif.');
        }

        DB::beginTransaction();
        try {
            // Simpan data lama untuk dikembalikan jika perlu
            $oldDriverId = $driverRequest->driver_id;
            $oldVehicleId = $driverRequest->vehicle_id;
            $oldVoucherId = $driverRequest->voucher_id;

            // Set current business unit
            if (!$driverRequest->original_business_unit_id) {
                $driverRequest->original_business_unit_id = $driverRequest->requester->drmsProfile->business_unit_id;
            }
            $driverRequest->current_business_unit_id = $targetBuId;
            $driverRequest->forwarded_by_user_id = $user->id;
            $driverRequest->forwarded_at = now();

            // Reset data assignment karena akan diproses ulang oleh admin BU tujuan
            $driverRequest->admin_id = null;
            $driverRequest->driver_id = null;
            $driverRequest->vehicle_id = null;
            $driverRequest->voucher_id = null;
            $driverRequest->transport_type = null;
            $driverRequest->status = 'approved_l1';
            $driverRequest->approved_admin_at = null;
            $driverRequest->rejection_reason = null;

            $driverRequest->save();

            // Update status driver/kendaraan/voucher lama menjadi available
            if ($oldDriverId) Driver::where('id', $oldDriverId)->update(['status' => 'available']);
            if ($oldVehicleId) Vehicle::where('id', $oldVehicleId)->update(['status' => 'available']);
            if ($oldVoucherId) Voucher::where('id', $oldVoucherId)->update(['status' => 'available']);

            // Kirim notifikasi ke semua admin BU tujuan
            Notification::send($targetAdmins, new RequestForwardedNotification($driverRequest, $user, $request->note));

            DB::commit();

            return redirect()->route('drms.approval.admin.index')
                ->with('success', "Permintaan berhasil dialihkan ke Business Unit tujuan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal mengalihkan permintaan: ' . $e->getMessage());
        }
    }

    /**
     * Helper untuk mengecek apakah admin memiliki akses terhadap request tertentu.
     * Berdasarkan current_business_unit_id atau BU requester.
     *
     * @param DriverRequest $driverRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function authorizeAdminAccess(DriverRequest $driverRequest)
    {
        $user = Auth::user();
        $adminProfile = $user->drmsProfile;

        if (!$adminProfile) {
            abort(403, 'Profil admin tidak ditemukan.');
        }

        $requesterBu = $driverRequest->requester->drmsProfile->business_unit_id ?? null;
        $currentBu = $driverRequest->current_business_unit_id ?? $requesterBu;

        if ($adminProfile->business_unit_id != $currentBu) {
            abort(403, 'Anda tidak memiliki akses ke permintaan ini.');
        }
    }
}