<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\DriverRequest;
use App\Models\User;
use App\Notifications\RequestApprovedL1Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class AppL1Controller extends Controller
{
    public function index()
    {
        $pendingRequests = DriverRequest::with('requester')
            ->where('approver_l1_id', Auth::id())
            ->where('status', 'pending_l1')
            ->latest()
            ->get();

        $historyRequests = DriverRequest::with('requester')
            ->where('approver_l1_id', Auth::id())
            ->whereIn('status', ['approved_l1', 'rejected_l1', 'approved_admin', 'rejected_admin'])
            ->latest()
            ->paginate(10);

        return view('drms.approval.l1.index', compact('pendingRequests', 'historyRequests'));
    }

    public function approve($id)
    {
        $driverRequest = DriverRequest::where('approver_l1_id', Auth::id())->findOrFail($id);
        $driverRequest->update([
            'status' => 'approved_l1',
            'approved_l1_at' => now(),
        ]);

        $requester = $driverRequest->requester;
        $profile = $requester->drmsProfile;
        if (!$profile) {
            return redirect()->back()->withErrors('Profil pemohon tidak lengkap.');
        }

        $businessUnitId = $profile->business_unit_id;
        $area = $profile->area;

        $admins = User::whereHas('drmsProfile', function ($q) use ($businessUnitId, $area) {
            $q->where('is_drms_admin', true)
              ->where('business_unit_id', $businessUnitId);
            if ($area) {
                $q->where('area', $area);
            }
        })->get();

        Notification::send($admins, new RequestApprovedL1Notification($driverRequest));

        return redirect()->route('drms.approval.l1.index')
            ->with('success', 'Request disetujui.');
    }

    public function reject(Request $request, $id)
    {
        $data = $request->validate([
            'rejection_reason' => 'required|string'
        ]);

        $driverRequest = DriverRequest::where('approver_l1_id', Auth::id())->findOrFail($id);
        $driverRequest->update([
            'status' => 'rejected_l1',
            'rejection_reason' => $data['rejection_reason'],
        ]);

        return redirect()->route('drms.approval.l1.index')
            ->with('success', 'Request ditolak.');
    }
}