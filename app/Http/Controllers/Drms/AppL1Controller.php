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
    /**
     * Tampilkan daftar request yang perlu disetujui atasan ini.
     */
    public function index()
    {
        $pendingRequests = DriverRequest::with('requester')
            ->where('approver_l1_id', Auth::id())
            ->where('status', 'pending_l1')
            ->latest()
            ->get();

        // History: semua request yang pernah diproses oleh atasan ini (termasuk yang sudah diproses admin)
        $historyRequests = DriverRequest::with('requester')
            ->where('approver_l1_id', Auth::id())
            ->whereIn('status', ['approved_l1', 'rejected_l1', 'approved_admin', 'rejected_admin'])
            ->latest()
            ->paginate(10);

        return view('drms.approval.l1.index', compact('pendingRequests', 'historyRequests'));
    }

    /**
     * Setujui request
     */
    public function approve($id)
    {
        $driverRequest = DriverRequest::where('approver_l1_id', Auth::id())->findOrFail($id);
        $driverRequest->update([
            'status' => 'approved_l1',
            'approved_l1_at' => now(),
        ]);

        // Kirim notifikasi ke admin dengan unit dan area yang sama
        $requester = $driverRequest->requester;
        $businessUnitId = $requester->business_unit_id;
        $area = $requester->area;

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

    /**
     * Tolak request dengan alasan
     */
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

        // Opsional: kirim notifikasi ke requester

        return redirect()->route('drms.approval.l1.index')
            ->with('success', 'Request ditolak.');
    }
}