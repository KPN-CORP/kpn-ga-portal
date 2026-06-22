<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\DriverRequest;
use App\Models\Drms\Driver;
use App\Models\Drms\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DriverDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $driver = $user->driver;

        if (!$driver) {
            abort(403, 'Data driver tidak ditemukan.');
        }

        $date = $request->get('date');

        $upcomingQuery = DriverRequest::with(['requester', 'vehicle', 'voucher'])
            ->where('driver_id', $driver->id)
            ->where('status', 'approved_admin')
            ->orderBy('usage_date', 'asc')
            ->orderBy('start_time', 'asc');

        $historyQuery = DriverRequest::with(['requester', 'vehicle', 'voucher'])
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['completed', 'rejected_admin'])
            ->orderBy('usage_date', 'desc')
            ->orderBy('start_time', 'desc');

        if ($date) {
            $upcomingQuery->whereDate('usage_date', '>=', $date);
        }

        $upcomingRequests = $upcomingQuery->get();
        $historyRequests = $historyQuery->paginate(10);

        // Cek apakah sudah ada log untuk setiap perjalanan
        foreach ($upcomingRequests as $req) {
            $req->hasLog = \App\Models\Drms\TripLog::where('request_id', $req->id)->exists();
            $req->logSubmitted = \App\Models\Drms\TripLog::where('request_id', $req->id)
                ->where('is_submitted', 1)
                ->exists();
            $req->logVerified = \App\Models\Drms\TripLog::where('request_id', $req->id)
                ->where('is_verified', 1)
                ->exists();
        }

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

    /**
     * Menyelesaikan perjalanan oleh driver.
     * Memastikan kendaraan ikut berubah status menjadi available.
     */
    public function complete(DriverRequest $driverRequest)
    {
        $user = Auth::user();
        $driver = $user->driver;

        if (!$driver || $driverRequest->driver_id !== $driver->id) {
            abort(403, 'Anda tidak memiliki akses ke request ini.');
        }

        if ($driverRequest->status !== 'approved_admin') {
            return back()->withErrors('Hanya request aktif yang bisa diselesaikan.');
        }

        DB::beginTransaction();
        try {
            // 1. Update status request menjadi completed
            $driverRequest->update(['status' => 'completed']);

            // 2. Update driver menjadi available
            Driver::where('id', $driver->id)->update(['status' => 'available']);

            // 3. Update kendaraan menjadi available (jika ada)
            if ($driverRequest->vehicle_id) {
                $vehicleUpdated = Vehicle::where('id', $driverRequest->vehicle_id)
                    ->update(['status' => 'available']);
                
                if (!$vehicleUpdated) {
                    throw new \Exception("Kendaraan dengan ID {$driverRequest->vehicle_id} tidak ditemukan atau gagal diupdate.");
                }
                
                Log::info("Driver {$driver->name} menyelesaikan request {$driverRequest->request_no}, kendaraan ID {$driverRequest->vehicle_id} sekarang available.");
            } else {
                Log::warning("Request {$driverRequest->request_no} tidak memiliki vehicle_id, kendaraan tidak diupdate.");
            }

            DB::commit();
            
            return redirect()->route('drms.driver.dashboard')
                ->with('success', 'Perjalanan berhasil diselesaikan. Kendaraan sudah tersedia kembali.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyelesaikan perjalanan driver: ' . $e->getMessage(), [
                'request_id' => $driverRequest->id,
                'driver_id' => $driver->id,
                'vehicle_id' => $driverRequest->vehicle_id
            ]);
            return back()->withErrors('Gagal menyelesaikan perjalanan: ' . $e->getMessage());
        }
    }
}