<?php

namespace App\Http\Controllers\IDCard;

use App\Exports\IDCard\IdCardReportExport;
use App\Exports\IDCard\IdCardWorkbookExport;
use App\Exports\IDCard\SheetArrayExport;
use App\Models\IDCard\IdCardKartuPinjam;
use App\Models\IDCard\IdCardStokLog;
use App\Models\IDCard\RequestIdCard;
use App\Services\IDCardStokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends IDCardBaseController
{
    public function index(Request $request)
    {
        if (!$this->isSuperAdmin() && empty($this->getAdminBUAccess())) {
            return redirect()->route('no-access')->with('error', 'Anda tidak memiliki akses ke halaman report.');
        }

        $bisnisUnits = DB::table('tb_bisnis_unit')->get();

        $data = $this->filteredQuery($request)->paginate(10)->withQueryString();

        // Ringkasan stok MILIK admin yang membuka report, dan kartu pinjam. Kosong bila tabel stok belum dibuat.
        $stokRingkasan = collect();
        $kartuRingkas  = null;
        try {
            $svc = app(IDCardStokService::class);
            $stokRingkasan = $svc->ringkasan((int) Auth::id());
            $kartuRingkas = $svc->hitungKartuPinjam();
        } catch (\Throwable $e) {
            // abaikan: report request tetap tampil
        }

        return view('idcard.report', compact('data', 'bisnisUnits', 'stokRingkasan', 'kartuRingkas'));
    }

    public function download(Request $request)
    {
        if (!$this->isSuperAdmin() && empty($this->getAdminBUAccess())) {
            return redirect()->route('no-access')->with('error', 'Anda tidak memiliki akses untuk mendownload report.');
        }

        $sheets = [new IdCardReportExport($this->filteredQuery($request))];

        try {
            $svc = app(IDCardStokService::class);

            // Sheet stok per admin: superadmin melihat semua admin, admin BU hanya stok dan mutasi miliknya
            if (Schema::hasTable('idcard_item')) {
                $statusLabel = ['aman' => 'Aman', 'menipis' => 'Perlu beli', 'habis' => 'Habis'];
                $items       = $svc->items();

                if ($this->isSuperAdmin()) {
                    $stokRows = [];
                    foreach ($svc->matriksSemuaAdmin() as $m) {
                        foreach ($items as $i) {
                            $c = $m->stok[$i->id];
                            $stokRows[] = [$m->user->name, $i->nama, $c->stok, $c->min_stok, max(0, $c->min_stok - $c->stok), $statusLabel[$c->status] ?? $c->status];
                        }
                    }
                } else {
                    $namaSaya = optional(Auth::user())->name;
                    $stokRows = $svc->ringkasan((int) Auth::id())->map(fn ($r) => [
                        $namaSaya, $r->nama, $r->stok, $r->min_stok, $r->kurang, $statusLabel[$r->status] ?? $r->status,
                    ])->all();
                }

                $sheets[] = new SheetArrayExport(
                    'Stok Saat Ini',
                    ['Admin', 'Item', 'Stok', 'Stok Minimum', 'Kurang dari Minimum', 'Status'],
                    $stokRows
                );

                $log = IdCardStokLog::with(['item', 'creator', 'owner'])->orderBy('id');
                if (!$this->isSuperAdmin()) {
                    $log->where('user_id', Auth::id());
                }
                if ($request->periode_awal) {
                    $log->whereDate('created_at', '>=', $request->periode_awal);
                }
                if ($request->periode_akhir) {
                    $log->whereDate('created_at', '<=', $request->periode_akhir);
                }
                $sheets[] = new SheetArrayExport(
                    'Mutasi Stok',
                    ['Tanggal', 'Pemilik Stok (Admin)', 'Item', 'Jenis', 'Jumlah', 'Saldo Setelah', 'ID Request', 'Keterangan', 'Dicatat Oleh'],
                    $log->get()->map(fn ($l) => [
                        optional($l->created_at)->format('d-m-Y H:i'),
                        optional($l->owner)->name,
                        optional($l->item)->nama,
                        $l->tipe_text,
                        ($l->qty > 0 ? '+' : '') . $l->qty,
                        $l->saldo_setelah,
                        $l->request_id,
                        $l->catatan,
                        optional($l->creator)->name,
                    ])->all()
                );
            }

            // Sheet kartu pinjam: pemakai dari BU lain disamarkan untuk admin BU
            if (Schema::hasTable('idcard_kartu_pinjam')) {
                $isSuper = $this->isSuperAdmin();
                $adminBU = $this->getAdminBUAccess();
                $label   = ['digunakan' => 'Digunakan', 'ada' => 'Ada', 'nonaktif' => 'Tidak beredar'];
                $fmt     = fn ($d) => $d ? $d->format('d-m-Y') : '';

                $sheets[] = new SheetArrayExport(
                    'Kartu Pinjam',
                    ['Nomor Kartu', 'Jenis', 'Status', 'Dipakai Oleh', 'NIK', 'Kategori', 'Bisnis Unit', 'Masa Berlaku', 'Sampai Tanggal', 'Keterangan Kartu'],
                    $svc->statusKartuPinjam(IdCardKartuPinjam::orderBy('nomor_kartu')->get())->map(function ($r) use ($isSuper, $adminBU, $label, $fmt) {
                        $p     = $r->pemakai;
                        $boleh = $p && ($isSuper || in_array($p->bisnis_unit_id, $adminBU));
                        return [
                            $r->kartu->nomor_kartu,
                            $r->kartu->jenis_text,
                            $label[$r->status] ?? $r->status,
                            $p ? ($boleh ? $p->nama : 'Bisnis unit lain') : '',
                            $boleh ? $p->nik : '',
                            $boleh ? $p->kategori_text : '',
                            $boleh ? optional($p->bisnisUnit)->nama_bisnis_unit : '',
                            $boleh ? $fmt($p->masa_berlaku) : '',
                            $boleh ? $fmt($p->sampai_tanggal) : '',
                            $r->kartu->keterangan,
                        ];
                    })->all()
                );
            }
        } catch (\Throwable $e) {
            \Log::warning('IDCard report: sheet tambahan dilewati: ' . $e->getMessage());
        }

        return Excel::download(new IdCardWorkbookExport($sheets), 'IDCard_Report.xlsx');
    }

    /** Query request sesuai filter + hak akses (dipakai halaman report dan download Excel). */
    private function filteredQuery(Request $request)
    {
        $query = RequestIdCard::orderBy('created_at', 'desc')->orderByDesc('id');
        $query = $this->applyAccessFilter($query);

        // Item diminta (Card Holder / Lanyard / Kartu); dilewati bila tabel belum dibuat
        if (Schema::hasTable('request_idcard_item')) {
            $query->with('items.item');
        }

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

        return $query;
    }
}
