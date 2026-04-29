<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\DriverRequest;
use App\Models\Drms\Driver;
use App\Models\Drms\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DriverDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $driver = $user->driver;

        if (!$driver) {
            abort(403, 'Data driver tidak ditemukan.');
        }

        // Ambil parameter filter tanggal (jika ada)
        $date = $request->get('date');

        // Jadwal Aktif (status approved_admin)
        $upcomingQuery = DriverRequest::with(['requester', 'vehicle', 'voucher'])
            ->where('driver_id', $driver->id)
            ->where('status', 'approved_admin')
            ->orderBy('usage_date', 'asc')
            ->orderBy('start_time', 'asc');

        // History (completed atau rejected_admin)
        $historyQuery = DriverRequest::with(['requester', 'vehicle', 'voucher'])
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['completed', 'rejected_admin'])
            ->orderBy('usage_date', 'desc')
            ->orderBy('start_time', 'desc');

        // Hanya batasi jadwal aktif jika ada filter tanggal
        if ($date) {
            $upcomingQuery->whereDate('usage_date', '>=', $date);
            // History tidak ikut difilter tanggal (opsional)
        }

        $upcomingRequests = $upcomingQuery->get();
        $historyRequests = $historyQuery->paginate(10);

        return view('drms.drivers.dashboard', compact('driver', 'upcomingRequests', 'historyRequests', 'date'));
    }

    public function show(DriverRequest $driverRequest)
    {
        $user = Auth::user();
        $driver = $user->driver;

        if ($driverRequest->driver_id !== $driver->id) {
            abort(403);
        }

        return view('drms.drivers.show', compact('driverRequest'));
    }

    public function complete(DriverRequest $driverRequest)
    {
        $user = Auth::user();
        $driver = $user->driver;

        if ($driverRequest->driver_id !== $driver->id) {
            abort(403);
        }

        if ($driverRequest->status !== 'approved_admin') {
            return back()->withErrors('Hanya request aktif yang bisa diselesaikan.');
        }

        DB::beginTransaction();
        try {
            $driverRequest->update(['status' => 'completed']);
            Driver::where('id', $driver->id)->update(['status' => 'available']);
            if ($driverRequest->vehicle_id) {
                Vehicle::where('id', $driverRequest->vehicle_id)->update(['status' => 'available']);
            }
            DB::commit();
            return redirect()->route('drms.driver.dashboard')
                ->with('success', 'Perjalanan berhasil diselesaikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal menyelesaikan perjalanan.');
        }
    }
}