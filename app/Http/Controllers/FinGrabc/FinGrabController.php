<?php

namespace App\Http\Controllers\FinGrabc;

use App\Http\Controllers\Controller;
use App\Services\FinGrab\FinGrabIdFix;
use App\Services\FinGrab\FinGrabMatchReport;
use App\Services\FinGrab\FinGrabPayload;
use App\Support\FinGrabAccess;
use Illuminate\Http\Request;

/**
 * Tiga halaman dashboard, masing-masing punya route dan view sendiri:
 * Dashboard, Karyawan dan unit, Kecocokan data. Filter tersimpan di browser (sessionStorage) antar halaman.
 */
class FinGrabController extends Controller
{
    public function index()
    {
        return redirect()->route('fingrab.dashboard');
    }

    public function dashboard()
    {
        return $this->page('fingrab.dashboard');
    }

    public function karyawan()
    {
        return $this->page('fingrab.karyawan-unit');
    }

    public function kecocokan()
    {
        return $this->page('fingrab.kecocokan', true);
    }

    /** Perbaiki ID karyawan manual dari halaman Kecocokan data. Hanya admin Corporate (sama seperti upload). */
    public function fixId(Request $request)
    {
        $scope = FinGrabAccess::scope(auth()->user());
        if (!$scope['allowed'] || !$scope['all']) {
            return response()->json(['message' => 'Hanya admin Corporate yang boleh memperbaiki ID karyawan.'], 403);
        }

        $data = $request->validate([
            'kind' => ['required', 'in:id,email,name'],
            'value' => ['required', 'string', 'max:255'],
            'employee_id' => ['required', 'string', 'max:20'],
            'dry' => ['sometimes', 'boolean'],
        ]);

        $result = FinGrabIdFix::apply($data['kind'], $data['value'], $data['employee_id'], auth()->id(), (bool) ($data['dry'] ?? false));
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 422);
        }

        return response()->json($result);
    }

    private function page(string $view, bool $report = false)
    {
        $scope = FinGrabAccess::scope(auth()->user());
        if (!$scope['allowed']) {
            return FinGrabAccess::deny('Anda tidak memiliki akses ke E-FinGrab.');
        }

        // Data sudah dipotong per Bisnis unit di query sebelum dikirim ke halaman.
        $data = ['X' => FinGrabPayload::build($scope), 'label' => $scope['label'], 'canFix' => $scope['all']];
        if ($report) {
            $data['M'] = FinGrabMatchReport::build($scope);
        }

        return view($view, $data);
    }
}