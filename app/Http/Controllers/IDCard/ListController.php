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
        $query = RequestIdCard::orderBy('created_at', 'desc');
        $query = $this->applyAccessFilter($query);

        // Filter
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', "%{$request->search}%")
                  ->orWhere('nik', 'like', "%{$request->search}%");
            });
        }
        if ($request->status && $request->status != 'all') {
            $query->where('status', $request->status);
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
        // Periode
        if ($request->periode && $request->periode != 'all') {
            $today = now()->toDateString();
            switch ($request->periode) {
                case 'masa_aktif':
                    $query->where('masa_berlaku', '<=', $today)
                          ->where('sampai_tanggal', '>=', $today);
                    break;
                case 'masa_tidak_aktif':
                    $query->where(function ($q) use ($today) {
                        $q->where('masa_berlaku', '>', $today)
                          ->orWhere('sampai_tanggal', '<', $today);
                    });
                    break;
                case 'masa_habis_segera':
                    $thirtyDays = now()->addDays(30)->toDateString();
                    $query->where('sampai_tanggal', '>=', $today)
                          ->where('sampai_tanggal', '<=', $thirtyDays);
                    break;
            }
        }

        $data = $query->paginate($request->get('per_page', 10))->withQueryString();

        $bisnisUnits = collect();
        if ($this->isSuperAdmin()) {
            $bisnisUnits = DB::table('tb_bisnis_unit')->get();
        } else {
            $buIds = $this->getAdminBUAccess();
            if (!empty($buIds)) {
                $bisnisUnits = DB::table('tb_bisnis_unit')
                    ->whereIn('id_bisnis_unit', $buIds)
                    ->get();
            }
        }

        $hasSpecialAccess = $this->canProses();
        $statusLabels = [
            'pending'  => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak'
        ];

        return view('idcard.list', compact('data', 'bisnisUnits', 'statusLabels', 'hasSpecialAccess'));
    }

    // ===================== AKTIF (is_active = 1) =====================
    public function aktif(Request $request)
    {
        $query = RequestIdCard::orderBy('created_at', 'desc')
            ->where('is_active', 1);
        $query = $this->applyAccessFilter($query);

        // Filter tambahan
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
        // Filter periode (masa aktif berdasarkan tanggal)
        if ($request->periode && $request->periode != 'all') {
            $today = now()->toDateString();
            switch ($request->periode) {
                case 'masa_aktif':
                    $query->where('masa_berlaku', '<=', $today)
                        ->where('sampai_tanggal', '>=', $today);
                    break;
                case 'masa_tidak_aktif':
                    $query->where(function ($q) use ($today) {
                        $q->where('masa_berlaku', '>', $today)
                        ->orWhere('sampai_tanggal', '<', $today);
                    });
                    break;
                case 'masa_habis_segera':
                    $thirtyDays = now()->addDays(30)->toDateString();
                    $query->where('sampai_tanggal', '>=', $today)
                        ->where('sampai_tanggal', '<=', $thirtyDays);
                    break;
            }
        }

        $data = $query->paginate(10)->withQueryString();

        $bisnisUnits = collect();
        if ($this->isSuperAdmin()) {
            $bisnisUnits = DB::table('tb_bisnis_unit')->get();
        } else {
            $buIds = $this->getAdminBUAccess();
            if (!empty($buIds)) {
                $bisnisUnits = DB::table('tb_bisnis_unit')
                    ->whereIn('id_bisnis_unit', $buIds)
                    ->get();
            }
        }

        $canNonaktifkan = $this->isSuperAdmin() || !empty($this->getAdminBUAccess());

        return view('idcard.aktif', compact('data', 'bisnisUnits', 'canNonaktifkan'));
    }

    // ===================== INAKTIF (is_active = 0) =====================
    public function inaktif(Request $request)
    {
        $query = RequestIdCard::orderBy('created_at', 'desc')
            ->where('is_active', 0);
        $query = $this->applyAccessFilter($query);

        // Filter tambahan (sama seperti aktif)
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
        if ($request->periode && $request->periode != 'all') {
            $today = now()->toDateString();
            switch ($request->periode) {
                case 'masa_aktif':
                    $query->where('masa_berlaku', '<=', $today)
                        ->where('sampai_tanggal', '>=', $today);
                    break;
                case 'masa_tidak_aktif':
                    $query->where(function ($q) use ($today) {
                        $q->where('masa_berlaku', '>', $today)
                        ->orWhere('sampai_tanggal', '<', $today);
                    });
                    break;
                case 'masa_habis_segera':
                    $thirtyDays = now()->addDays(30)->toDateString();
                    $query->where('sampai_tanggal', '>=', $today)
                        ->where('sampai_tanggal', '<=', $thirtyDays);
                    break;
            }
        }

        $data = $query->paginate(10)->withQueryString();

        $bisnisUnits = collect();
        if ($this->isSuperAdmin()) {
            $bisnisUnits = DB::table('tb_bisnis_unit')->get();
        } else {
            $buIds = $this->getAdminBUAccess();
            if (!empty($buIds)) {
                $bisnisUnits = DB::table('tb_bisnis_unit')
                    ->whereIn('id_bisnis_unit', $buIds)
                    ->get();
            }
        }

        return view('idcard.inaktif', compact('data', 'bisnisUnits'));
    }
}