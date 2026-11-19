<?php

namespace App\Http\Controllers\IDCard;

use App\Models\IDCard\RequestIdCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListController extends IDCardBaseController
{
    // ===================== SEMUA REQUEST =====================
    public function index(Request $request)
    {
        $query = RequestIdCard::orderBy('created_at', 'desc')->orderByDesc('id');
        $query = $this->applyAccessFilter($query);
        $this->applyCommonFilters($query, $request);

        if ($request->status && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        $data = $query->paginate($request->get('per_page', 10))->withQueryString();

        $bisnisUnits      = $this->bisnisUnitOptions();
        $hasSpecialAccess = $this->canProses();
        $statusLabels     = RequestIdCard::STATUS_LABELS;

        return view('idcard.list', compact('data', 'bisnisUnits', 'statusLabels', 'hasSpecialAccess'));
    }

    // ===================== AKTIF (is_active = 1) =====================
    public function aktif(Request $request)
    {
        $query = RequestIdCard::orderBy('created_at', 'desc')->orderByDesc('id')
            ->where('status', 'approved')   // pending/rejected tidak boleh tampil sebagai kartu aktif
            ->where('is_active', 1)
            ->terkini();                    // baris lama yang sudah di-extend tidak tampil dobel
        $query = $this->applyAccessFilter($query);
        $this->applyCommonFilters($query, $request);

        $data = $query->paginate(10)->withQueryString();

        $bisnisUnits    = $this->bisnisUnitOptions();
        $canNonaktifkan = $this->canProses();

        return view('idcard.aktif', compact('data', 'bisnisUnits', 'canNonaktifkan'));
    }

    // ===================== INAKTIF (is_active = 0) =====================
    public function inaktif(Request $request)
    {
        $query = RequestIdCard::orderBy('created_at', 'desc')->orderByDesc('id')
            ->where('status', 'approved')
            ->where('is_active', 0);
        $query = $this->applyAccessFilter($query);
        $this->applyCommonFilters($query, $request);

        $data = $query->paginate(10)->withQueryString();

        $bisnisUnits    = $this->bisnisUnitOptions();
        $canNonaktifkan = $this->canProses();

        return view('idcard.inaktif', compact('data', 'bisnisUnits', 'canNonaktifkan'));
    }

    // ===================== HELPER =====================

    /**
     * Filter yang sama di tiga halaman (sebelumnya disalin 3x).
     */
    private function applyCommonFilters($query, Request $request): void
    {
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', "%{$request->search}%")
                  ->orWhere('nik', 'like', "%{$request->search}%");
            });
        }
        if ($request->kategori && $request->kategori != 'all') {
            $query->where('kategori', $request->kategori);
        }
        if ($request->nomor_kartu) {
            $query->where('nomor_kartu', 'like', "%{$request->nomor_kartu}%");
        }
        if ($this->isSuperAdmin() && $request->bisnis_unit_id && $request->bisnis_unit_id != 'all') {
            $query->where('bisnis_unit_id', $request->bisnis_unit_id);
        }
        if ($request->return_status && $request->return_status != 'all'
            && array_key_exists($request->return_status, RequestIdCard::RETURN_STATUS_LABELS)) {
            $query->where('return_status', $request->return_status);
        }

        $query->filterPeriode($request->periode);
    }

    private function bisnisUnitOptions()
    {
        if ($this->isSuperAdmin()) {
            return DB::table('tb_bisnis_unit')->get();
        }

        $buIds = $this->getAdminBUAccess();
        if (!empty($buIds)) {
            return DB::table('tb_bisnis_unit')->whereIn('id_bisnis_unit', $buIds)->get();
        }

        return collect();
    }
}
