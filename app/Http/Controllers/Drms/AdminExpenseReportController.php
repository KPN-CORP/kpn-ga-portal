<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\BisnisUnit;
use App\Models\Drms\Driver;
use App\Models\Drms\DriverRequest;
use App\Models\Drms\ExpenseReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Laporan Pengeluaran driver untuk ADMIN & SUPERADMIN (read-only).
 *
 * - Admin biasa: hanya laporan dari request di BU-nya (dan area-areanya kalau ia memegang area),
 *   memakai scope yang sama dengan halaman Approval Admin (DriverRequest::scopeInAdminScope).
 * - Superadmin: semua BU, plus filter BU.
 * - Tidak ada edit / hapus di sini. Aturan edit 10 hari dan larangan hapus tetap milik driver
 *   (DriverExpenseReportController tidak diubah).
 */
class AdminExpenseReportController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * Query ExpenseReport yang dibatasi cakupan admin (BU/area). Belum ada filter lain.
     */
    private function scopedExpenses($user)
    {
        $profile = $user->drmsProfile;

        if (!$user->isDrmsSuperAdmin() && !$profile) {
            abort(403, 'Profil DRMS tidak ditemukan.');
        }

        $query = ExpenseReport::query()->whereNotNull('request_id');

        if (!$user->isDrmsSuperAdmin()) {
            $businessUnitId = $profile->business_unit_id ?? null;
            $areas = $profile->adminAreaList();

            $query->whereHas('request', function ($q) use ($businessUnitId, $areas) {
                $q->inAdminScope($businessUnitId, $areas);
            });
        }

        return $query;
    }

    /**
     * Filter bulan (default: bulan sekarang). 'all' untuk semua periode.
     */
    private function applyMonthFilter($query, string $month)
    {
        if ($month !== 'all' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            [$year, $monthNum] = explode('-', $month);
            $query->whereYear('report_date', $year)->whereMonth('report_date', $monthNum);
        }
        return $query;
    }

    /**
     * Daftar laporan pengeluaran semua driver dalam cakupan admin,
     * dikelompokkan PER PERJALANAN (1 perjalanan = 1 laporan), sama seperti tampilan driver.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuper = $user->isDrmsSuperAdmin();

        $month = $request->get('month', now()->format('Y-m'));
        $category = $request->get('category');
        $validCategories = array_keys(ExpenseReport::CATEGORIES);
        if (!in_array($category, $validCategories, true)) {
            $category = null;
        }

        // Opsi dropdown driver: hanya driver yang punya laporan di cakupan admin ini.
        $driverIds = $this->scopedExpenses($user)->select('driver_id')->distinct();
        $driverOptions = Driver::whereIn('id', $driverIds)->orderBy('name')->get(['id', 'name']);

        $query = $this->scopedExpenses($user)
            ->with(['driver', 'request.requester.drmsProfile.businessUnit', 'request.currentBusinessUnit']);

        $this->applyMonthFilter($query, $month);

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }
        if ($category) {
            $query->where('category', $category);
        }
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->whereHas('request', function ($q) use ($term) {
                $q->where('request_no', 'LIKE', $term)
                  ->orWhere('destination', 'LIKE', $term)
                  ->orWhere('pickup_location', 'LIKE', $term);
            });
        }
        // Filter BU hanya untuk superadmin (untuk admin biasa parameter ?bu= diabaikan).
        if ($isSuper && $request->filled('bu')) {
            $buId = $request->bu;
            $query->whereHas('request', function ($q) use ($buId) {
                $q->inAdminScope($buId, []);
            });
        }

        $items = $query->get();

        $trips = $items->groupBy('request_id')->map(function ($group) {
            $first = $group->first();
            return (object) [
                'request'      => $first->request,
                'driver'       => $first->driver,
                'items'        => $group,
                'total'        => $group->sum('amount'),
                'is_editable'  => $group->every->is_editable,
                'submitted_at' => $first->created_at,
            ];
        })->filter(fn ($trip) => $trip->request !== null)
          ->sortByDesc(fn ($trip) => optional($trip->request)->usage_date)
          ->values();

        $grandTotal = $items->sum('amount');
        $totals = collect(ExpenseReport::CATEGORIES)->keys()->mapWithKeys(function ($cat) use ($items) {
            return [$cat => $items->where('category', $cat)->sum('amount')];
        });

        // Pagination manual karena data sudah dikelompokkan di collection.
        $page = LengthAwarePaginator::resolveCurrentPage();
        $trips = new LengthAwarePaginator(
            $trips->forPage($page, self::PER_PAGE)->values(),
            $trips->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $businessUnits = $isSuper ? BisnisUnit::orderBy('nama_bisnis_unit')->get() : collect();

        return view('drms.admin.expenses.index', compact(
            'trips', 'totals', 'grandTotal', 'month', 'category', 'driverOptions', 'businessUnits', 'isSuper'
        ));
    }

    /**
     * Detail laporan pengeluaran SATU perjalanan (read-only).
     */
    public function detail(DriverRequest $driverRequest)
    {
        $items = $this->itemsOrAbort($driverRequest);

        $totals = collect(ExpenseReport::CATEGORIES)->keys()->mapWithKeys(function ($cat) use ($items) {
            return [$cat => $items->where('category', $cat)->sum('amount')];
        });
        $grandTotal = $items->sum('amount');
        $driver = $items->first()->driver;
        $driverRequest->load(['requester.drmsProfile.businessUnit', 'vehicle']);

        return view('drms.admin.expenses.detail', compact('driverRequest', 'items', 'totals', 'grandTotal', 'driver'));
    }

    /**
     * Download PDF laporan SATU perjalanan (view khusus admin: lengkap dengan data driver, kendaraan, pemohon, BU).
     */
    public function pdf(DriverRequest $driverRequest)
    {
        $items = $this->itemsOrAbort($driverRequest);

        $totals = collect(ExpenseReport::CATEGORIES)->keys()->mapWithKeys(function ($cat) use ($items) {
            return [$cat => $items->where('category', $cat)->sum('amount')];
        });
        $grandTotal = $items->sum('amount');
        $driver = $items->first()->driver;
        $driverRequest->load(['requester.drmsProfile.businessUnit', 'vehicle']);
        $printedBy = Auth::user();

        $pdf = Pdf::loadView('drms.admin.expenses.pdf', compact('driverRequest', 'items', 'totals', 'grandTotal', 'driver', 'printedBy'))
            ->setPaper('a4', 'portrait');

        $filename = 'laporan-pengeluaran-' . Str::slug($driver->name) . '-trip-' . $driverRequest->id . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Ambil semua entri laporan untuk satu perjalanan, setelah memastikan perjalanan itu
     * ada dalam cakupan admin (dicek di server, bukan hanya disembunyikan di daftar).
     */
    private function itemsOrAbort(DriverRequest $driverRequest)
    {
        $user = Auth::user();

        if (!$user->isDrmsSuperAdmin()) {
            $inScope = DriverRequest::whereKey($driverRequest->id)
                ->inAdminScope(
                    optional($user->drmsProfile)->business_unit_id,
                    $user->drmsProfile ? $user->drmsProfile->adminAreaList() : []
                )
                ->exists();

            if (!$inScope) {
                abort(403, 'Anda tidak memiliki akses ke laporan perjalanan ini.');
            }
        }

        $items = ExpenseReport::with('driver')
            ->where('request_id', $driverRequest->id)
            ->orderBy('category')
            ->orderBy('report_date')
            ->get();

        if ($items->isEmpty()) {
            abort(404, 'Belum ada laporan pengeluaran untuk perjalanan ini.');
        }

        return $items;
    }
}