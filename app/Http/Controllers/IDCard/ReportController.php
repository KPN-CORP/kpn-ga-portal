<?php

namespace App\Http\Controllers\IDCard;

use App\Models\IDCard\RequestIdCard;
use App\Exports\IDCard\IdCardReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends IDCardBaseController
{
    public function index(Request $request)
    {
        if (!$this->isSuperAdmin() && empty($this->getAdminBUAccess())) {
            return redirect()->route('no-access')->with('error', 'Anda tidak memiliki akses ke halaman report.');
        }

        $bisnisUnits = DB::table('tb_bisnis_unit')->get();

        $query = RequestIdCard::orderBy('created_at', 'desc');
        $query = $this->applyAccessFilter($query);

        if ($request->bisnis_unit_id && $request->bisnis_unit_id != 'all') {
            $query->where('bisnis_unit_id', $request->bisnis_unit_id);
        }
        if ($request->status && $request->status != 'all') {
            $query->where('status', $request->status);
        }
        if ($request->kategori && $request->kategori != 'all') {
            $query->where('kategori', $request->kategori);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', "%{$request->search}%")
                  ->orWhere('nik', 'like', "%{$request->search}%");
            });
        }
        if ($request->periode_awal) {
            $query->whereDate('created_at', '>=', $request->periode_awal);
        }
        if ($request->periode_akhir) {
            $query->whereDate('created_at', '<=', $request->periode_akhir);
        }

        $data = $query->paginate(10)->withQueryString();

        return view('idcard.report', compact('data', 'bisnisUnits'));
    }

    public function download(Request $request)
    {
        if (!$this->isSuperAdmin() && empty($this->getAdminBUAccess())) {
            return redirect()->route('no-access')->with('error', 'Anda tidak memiliki akses untuk mendownload report.');
        }

        $query = RequestIdCard::orderBy('created_at', 'desc');
        $query = $this->applyAccessFilter($query);

        if ($request->bisnis_unit_id && $request->bisnis_unit_id != 'all') {
            $query->where('bisnis_unit_id', $request->bisnis_unit_id);
        }
        if ($request->status && $request->status != 'all') {
            $query->where('status', $request->status);
        }
        if ($request->kategori && $request->kategori != 'all') {
            $query->where('kategori', $request->kategori);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', "%{$request->search}%")
                  ->orWhere('nik', 'like', "%{$request->search}%");
            });
        }
        if ($request->periode_awal) {
            $query->whereDate('created_at', '>=', $request->periode_awal);
        }
        if ($request->periode_akhir) {
            $query->whereDate('created_at', '<=', $request->periode_akhir);
        }

        return Excel::download(new IdCardReportExport($query), 'IDCard_Report.xlsx');
    }
}