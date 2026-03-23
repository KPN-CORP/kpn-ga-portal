<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\Drms\DriverRequest;
use Illuminate\Support\Facades\Auth;

class DrmsSidebarComposer
{
    public function compose(View $view)
    {
        $user = Auth::user();
        $pendingL1Count = 0;
        $pendingAdminCount = 0;

        if ($user && method_exists($user, 'isApprover') && $user->isApprover()) {
            $pendingL1Count = DriverRequest::where('approver_l1_id', $user->id)
                ->where('status', 'pending_l1')
                ->count();
        }

        if ($user && method_exists($user, 'isDrmsAdmin') && $user->isDrmsAdmin()) {
            $businessUnitId = $user->business_unit_id ?? null;
            $area = $user->area ?? null;
            if ($businessUnitId) {
                $pendingAdminCount = DriverRequest::pendingAdmin($businessUnitId, $area)->count();
            }
        }

        $view->with([
            'pendingL1Count' => $pendingL1Count,
            'pendingAdminCount' => $pendingAdminCount,
        ]);
    }
}