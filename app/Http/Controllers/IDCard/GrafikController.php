<?php

namespace App\Http\Controllers\IDCard;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrafikController extends IDCardBaseController
{
    public function index()
    {
        if (!$this->isSuperAdmin() && empty($this->getAdminBUAccess())) {
            return redirect()->route('no-access')->with('error', 'Anda tidak memiliki akses ke halaman grafik.');
        }

        $bisnisUnits = DB::table('tb_bisnis_unit')->get();

        $query = DB::table('request_idcard')
            ->select('bisnis_unit_id', 'status', DB::raw('count(*) as total'))
            ->groupBy('bisnis_unit_id', 'status');

        $adminBU = $this->getAdminBUAccess();
        if (!empty($adminBU) && !$this->isSuperAdmin()) {
            $query->whereIn('bisnis_unit_id', $adminBU);
        }

        $stats = $query->get();

        $chartData = [];
        foreach ($bisnisUnits as $bu) {
            $buId = $bu->id_bisnis_unit;
            $chartData[$buId] = [
                'nama' => $bu->nama_bisnis_unit,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
            ];
        }

        foreach ($stats as $stat) {
            if (isset($chartData[$stat->bisnis_unit_id])) {
                $chartData[$stat->bisnis_unit_id][$stat->status] = $stat->total;
            }
        }

        $filteredData = [];
        foreach ($chartData as $buId => $data) {
            if (!empty($adminBU) && !$this->isSuperAdmin()) {
                if (!in_array($buId, $adminBU)) continue;
            }
            $filteredData[] = $data;
        }

        $chartLabels = array_column($filteredData, 'nama');
        $pendingData = array_column($filteredData, 'pending');
        $approvedData = array_column($filteredData, 'approved');
        $rejectedData = array_column($filteredData, 'rejected');

        return view('idcard.grafik', compact('chartLabels', 'pendingData', 'approvedData', 'rejectedData'));
    }
}