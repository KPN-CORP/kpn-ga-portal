<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\DriverRequest;
use App\Models\Drms\DrmsAdminArea;
use App\Models\User;
use App\Notifications\RequestApprovedL1Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class AppL1Controller extends Controller
{
    public function index(Request $request)
    {
        // Pending requests (tidak perlu filter, hanya tampilkan semua pending milik approver)
        $pendingRequests = DriverRequest::with('requester', 'attachments')
            ->where('approver_l1_id', Auth::id())
            ->where('status', 'pending_l1')
            ->latest()
            ->get();

        // History requests dengan filter search dan status
        $historyQuery = DriverRequest::with('requester', 'attachments')
            ->where('approver_l1_id', Auth::id())
            ->whereIn('status', ['approved_l1', 'rejected_l1', 'approved_admin', 'rejected_admin']);

        // Filter search (request_no, pemohon, pickup_location, destination)
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $historyQuery->where(function ($q) use ($search) {
                $q->where('request_no', 'LIKE', $search)
                  ->orWhere('pickup_location', 'LIKE', $search)
                  ->orWhere('destination', 'LIKE', $search)
                  ->orWhereHas('requester', function ($q2) use ($search) {
                      $q2->where('name', 'LIKE', $search);
                  });
            });
        }

        // Filter status
        if ($request->filled('status') && in_array($request->status, ['approved_l1', 'rejected_l1', 'approved_admin', 'rejected_admin'])) {
            $historyQuery->where('status', $request->status);
        }

        $historyRequests = $historyQuery->latest()->paginate(10)->appends($request->query());

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

        // Admin di BU yang sama. Kalau pemohon punya area: admin yang memegang area itu
        // (drms_admin_areas) + admin lintas area (tidak punya baris area).
        $admins = User::whereHas('drmsProfile', function ($q) use ($businessUnitId) {
            $q->where('is_drms_admin', true)
              ->where('business_unit_id', $businessUnitId);
        })->when($area, function ($q) use ($area) {
            $q->where(function ($sub) use ($area) {
                $sub->whereNotIn('id', DrmsAdminArea::select('user_id'))
                    ->orWhereIn('id', DrmsAdminArea::where('area', $area)->select('user_id'));
            });
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