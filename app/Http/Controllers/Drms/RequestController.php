<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\DriverRequest;
use App\Models\User;
use App\Notifications\NewRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    public function index()
    {
        $requests = DriverRequest::with(['requester', 'approverL1', 'admin', 'driver', 'vehicle', 'voucher'])
            ->where('requester_id', auth()->id())
            ->latest()
            ->paginate(10);
        return view('drms.requests.index', compact('requests'));
    }

    public function create()
    {
        return view('drms.requests.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'trip_type'             => 'required|in:one_way,round_trip',
            'usage_date'            => 'required|date|after_or_equal:today',
            'start_hour'            => 'required|integer|between:0,23',
            'start_minute'          => 'required|integer|between:0,59',
            'end_hour'              => 'required|integer|between:0,23',
            'end_minute'            => 'required|integer|between:0,59',
            'pickup_location'       => 'required|string|max:255',
            'destination'           => 'required|string|max:255',
            'purpose'               => 'required|string',
            'pickup_maps_link'      => 'nullable|string|max:500',
            'destination_maps_link' => 'nullable|string|max:500',
        ];

        if ($request->trip_type === 'round_trip') {
            $rules['return_date'] = 'required|date|after_or_equal:usage_date';
        }

        // Sebelum validasi, bersihkan input yang mungkin mengandung spasi
        $request->merge([
            'start_hour'   => trim($request->input('start_hour', '')),
            'start_minute' => trim($request->input('start_minute', '')),
            'end_hour'     => trim($request->input('end_hour', '')),
            'end_minute'   => trim($request->input('end_minute', '')),
        ]);

        $data = $request->validate($rules);

        // Format waktu
        $start_time = sprintf('%02d:%02d', (int)$data['start_hour'], (int)$data['start_minute']);
        $end_time   = sprintf('%02d:%02d', (int)$data['end_hour'], (int)$data['end_minute']);

        if ($end_time <= $start_time) {
            return back()->withErrors(['end_time' => 'Jam selesai harus setelah jam berangkat.'])->withInput();
        }

        $data['start_time'] = $start_time;
        $data['end_time']   = $end_time;
        unset($data['start_hour'], $data['start_minute'], $data['end_hour'], $data['end_minute']);

        if ($data['trip_type'] === 'round_trip' && !empty($data['return_date'])) {
            $data['return_time'] = $data['end_time'];
        }

        $data['request_no']   = 'DRQ' . date('Ymd') . rand(100, 999);
        $data['requester_id'] = Auth::id();

        $profile = Auth::user()->drmsProfile;
        if (!$profile) {
            return back()->withErrors(['error' => 'Profil DRMS Anda tidak lengkap.'])->withInput();
        }

        if ($profile->approver_user_id) {
            $data['approver_l1_id'] = $profile->approver_user_id;
        }

        DB::beginTransaction();
        try {
            $driverRequest = DriverRequest::create($data);

            if (!empty($data['approver_l1_id'])) {
                $atasan = User::find($data['approver_l1_id']);
                if ($atasan) {
                    $atasan->notify(new NewRequestNotification($driverRequest));
                }
            }

            DB::commit();
            return redirect()->route('drms.requests.index')
                ->with('success', 'Permintaan berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Gagal menyimpan DRMS request: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'data'    => $data,
            ]);
            return back()->withErrors(['error' => 'Gagal menyimpan: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(DriverRequest $driverRequest)
    {
        if ($driverRequest->requester_id !== Auth::id()) {
            abort(403);
        }
        return view('drms.requests.show', compact('driverRequest'));
    }
}