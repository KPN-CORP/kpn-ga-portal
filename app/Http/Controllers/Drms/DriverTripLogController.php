<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\DriverRequest;
use App\Models\Drms\TripLog;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DriverTripLogController extends Controller
{
    public function create($requestId)
    {
        $request = DriverRequest::with('driver')->findOrFail($requestId);
        
        $driver = Auth::user()->driver;
        if (!$driver || $request->driver_id !== $driver->id) {
            abort(403, 'Anda tidak memiliki akses ke perjalanan ini.');
        }

        $log = TripLog::where('request_id', $requestId)->first();
        if ($log && $log->is_verified) {
            return redirect()->back()->with('info', 'Log perjalanan ini sudah diverifikasi admin.');
        }

        if ($log && $log->needsRevision() && $log->revision_requested_at) {
            if (now()->diffInDays($log->revision_requested_at) >= 7) {
                return redirect()->back()->with('error', 'Batas waktu revisi 7 hari telah lewat. Log tidak dapat diperbaiki.');
            }
        }

        // Ambil odometer finish dari trip log kendaraan yang sama paling akhir,
        // supaya Start (km) otomatis terisi dan driver tidak perlu input ulang (double input).
        $previousOdometerFinish = null;
        if ($request->vehicle_id) {
            $previousLog = TripLog::whereHas('request', function ($q) use ($request, $requestId) {
                    $q->where('vehicle_id', $request->vehicle_id)
                      ->where('id', '!=', $requestId);
                })
                ->whereNotNull('odometer_finish')
                ->orderByDesc('id')
                ->first();
            $previousOdometerFinish = $previousLog->odometer_finish ?? null;
        }

        return view('drms.drivers.trip_log_form', compact('request', 'log', 'previousOdometerFinish'));
    }

    public function store(Request $request, $requestId)
    {
        $requestData = DriverRequest::findOrFail($requestId);
        $driver = Auth::user()->driver;
        if (!$driver || $requestData->driver_id !== $driver->id) {
            abort(403);
        }

        $log = TripLog::where('request_id', $requestId)->first();
        if ($log) {
            if ($log->is_verified) {
                return back()->withErrors('Log sudah diverifikasi, tidak dapat diubah.');
            }
            if ($log->is_submitted && !$log->is_verified && !$log->needsRevision()) {
                return back()->withErrors('Log sedang menunggu verifikasi, tidak dapat diubah.');
            }
            if ($log->needsRevision() && $log->revision_requested_at) {
                if (now()->diffInDays($log->revision_requested_at) >= 7) {
                    return back()->withErrors('Batas waktu revisi 7 hari telah lewat. Log tidak dapat diperbaiki.');
                }
            }
        }

        $this->validate($request, [
            'odometer_start' => 'nullable|integer|min:0',
            'odometer_finish' => 'nullable|integer|min:0|gte:odometer_start',
            'photo_before' => 'nullable|image|max:5120',
            'photo_after' => 'nullable|image|max:5120',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $log = TripLog::firstOrNew(['request_id' => $requestId]);

            $log->fill($request->only([
                'odometer_start', 'odometer_finish', 'notes'
            ]));

            // Safety net: jika odometer_start belum pernah tersimpan dan tidak dikirim
            // dari form (field Start (km) bersifat readonly di sisi tampilan), ambil
            // otomatis dari odometer_finish trip terakhir kendaraan yang sama.
            if (!$log->odometer_start && !$request->filled('odometer_start')) {
                $previousLog = TripLog::whereHas('request', function ($q) use ($requestData, $requestId) {
                        $q->where('vehicle_id', $requestData->vehicle_id)
                          ->where('id', '!=', $requestId);
                    })
                    ->whereNotNull('odometer_finish')
                    ->orderByDesc('id')
                    ->first();
                if ($previousLog) {
                    $log->odometer_start = $previousLog->odometer_finish;
                }
            }

            if ($request->hasFile('photo_before')) {
                if ($log->photo_before) ImageHelper::deleteImage($log->photo_before);
                $log->photo_before = ImageHelper::compressAndStore(
                    $request->file('photo_before'),
                    'trip_logs/before'
                );
            }
            if ($request->hasFile('photo_after')) {
                if ($log->photo_after) ImageHelper::deleteImage($log->photo_after);
                $log->photo_after = ImageHelper::compressAndStore(
                    $request->file('photo_after'),
                    'trip_logs/after'
                );
            }

            if ($request->has('submit') && $request->submit == '1') {
                $log->is_submitted = 1;
                $log->submitted_at = now();
                $log->is_verified = 0;
                $log->verified_by = null;
                $log->verified_at = null;
                $log->verification_notes = null;
                $log->revision_note = null;
                $log->revision_requested_at = null;
            } else {
                $log->is_submitted = 0;
            }

            $log->save();
            DB::commit();

            $message = $log->is_submitted ? 'Log berhasil dikirim ke admin.' : 'Log berhasil disimpan sebagai draft.';
            return redirect()->route('drms.driver.trip.log.create', $requestId)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal menyimpan log: ' . $e->getMessage());
        }
    }
}