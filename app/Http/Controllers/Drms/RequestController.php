<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\DriverRequest;
use App\Models\User;
use App\Notifications\NewRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $data = $request->validate([
            'usage_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'pickup_location' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'purpose' => 'nullable|string',
        ]);

        $data['request_no'] = 'DRQ' . date('Ymd') . rand(100, 999);
        $data['requester_id'] = Auth::id();

        $profile = Auth::user()->drmsProfile;
        if ($profile && $profile->approver_user_id) {
            $data['approver_l1_id'] = $profile->approver_user_id;
        }

        $driverRequest = DriverRequest::create($data);

        if (!empty($data['approver_l1_id'])) {
            $atasan = User::find($data['approver_l1_id']);
            $atasan->notify(new NewRequestNotification($driverRequest));
        }

        return redirect()->route('drms.requests.index')
            ->with('success', 'Permintaan berhasil dibuat.');
    }

    public function show(DriverRequest $driverRequest)
    {
        if ($driverRequest->requester_id !== Auth::id()) {
            abort(403);
        }
        return view('drms.requests.show', compact('driverRequest'));
    }
}