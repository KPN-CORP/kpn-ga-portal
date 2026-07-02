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

        // Cek apakah dalam masa revisi dan batas waktu
        if ($log && $log->needsRevision() && $log->revision_requested_at) {
            if (now()->diffInDays($log->revision_requested_at) >= 7) {
                return redirect()->back()->with('error', 'Batas waktu revisi 7 hari telah lewat. Log tidak dapat diperbaiki.');
            }
        }

        return view('drms.drivers.trip_log_form', compact('request', 'log'));
    }

    public function store(Request $request, $requestId)
    {
        $requestData = DriverRequest::findOrFail($requestId);
        $driver = Auth::user()->driver;
        if (!$driver || $requestData->driver_id !== $driver->id) {
            abort(403);
        }

        // Cek status log
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
            'fuel_type' => 'nullable|in:bensin,listrik',
            'fuel_volume' => 'nullable|numeric|min:0',
            'fuel_price_per_unit' => 'nullable|numeric|min:0',
            'fuel_cost' => 'nullable|numeric|min:0',
            'photo_before' => 'nullable|image|max:5120',
            'photo_after' => 'nullable|image|max:5120',
            'photo_fuel_receipt' => 'nullable|image|max:5120',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $log = TripLog::firstOrNew(['request_id' => $requestId]);
            
            $log->fill($request->only([
                'odometer_start', 'odometer_finish',
                'fuel_type', 'fuel_volume', 'fuel_price_per_unit',
                'notes'
            ]));

            if ($request->filled('fuel_volume') && $request->filled('fuel_price_per_unit')) {
                $log->fuel_cost = $request->fuel_volume * $request->fuel_price_per_unit;
            } elseif ($request->filled('fuel_cost')) {
                $log->fuel_cost = $request->fuel_cost;
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
            if ($request->hasFile('photo_fuel_receipt')) {
                if ($log->photo_fuel_receipt) ImageHelper::deleteImage($log->photo_fuel_receipt);
                $log->photo_fuel_receipt = ImageHelper::compressAndStore(
                    $request->file('photo_fuel_receipt'),
                    'trip_logs/receipt'
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
                $log->revision_requested_at = null; // reset revisi
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