<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\DriverRequest;
use App\Models\Drms\ExpenseReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class DriverExpenseReportController extends Controller
{
    /**
     * Ambil driver dari user yang sedang login, abort jika bukan driver.
     */
    private function driverOrAbort()
    {
        $driver = Auth::user()->driver;
        if (!$driver) {
            abort(403, 'Data driver tidak ditemukan.');
        }
        return $driver;
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
     * Daftar laporan pengeluaran, dikelompokkan PER PERJALANAN (1 perjalanan = 1 laporan).
     * Tiap baris di halaman ini mewakili satu perjalanan yang sudah diisi laporannya,
     * lengkap dengan total, status terkunci/masih bisa diedit, dan tombol download PDF.
     */
    public function index(Request $request)
    {
        $driver = $this->driverOrAbort();
        $month = $request->get('month', now()->format('Y-m'));

        $query = ExpenseReport::with('request')->where('driver_id', $driver->id)->whereNotNull('request_id');
        $this->applyMonthFilter($query, $month);
        $items = $query->get();

        $trips = $items->groupBy('request_id')->map(function ($group) {
            return (object) [
                'request'     => optional($group->first())->request,
                'items'       => $group,
                'total'       => $group->sum('amount'),
                'is_editable' => $group->every->is_editable,
                'submitted_at'=> optional($group->first())->created_at,
            ];
        })->filter(fn($trip) => $trip->request !== null)
          ->sortByDesc(fn($trip) => optional($trip->request)->usage_date)
          ->values();

        $grandTotal = $items->sum('amount');
        $totals = collect(ExpenseReport::CATEGORIES)->keys()->mapWithKeys(function ($cat) use ($items) {
            return [$cat => $items->where('category', $cat)->sum('amount')];
        });

        return view('drms.drivers.expenses.index', compact('trips', 'totals', 'grandTotal', 'month', 'driver'));
    }

    /**
     * Form input laporan pengeluaran. Dropdown perjalanan HANYA menampilkan
     * perjalanan yang belum pernah diisi laporan pengeluarannya (1 perjalanan = 1 laporan,
     * begitu sudah diisi, perjalanan itu tidak muncul lagi di sini).
     */
    public function create()
    {
        $driver = $this->driverOrAbort();

        $alreadyReported = ExpenseReport::where('driver_id', $driver->id)
            ->whereNotNull('request_id')
            ->pluck('request_id');

        $requests = DriverRequest::where('driver_id', $driver->id)
            ->whereIn('status', ['approved_admin', 'completed'])
            ->whereNotIn('id', $alreadyReported)
            ->orderByDesc('usage_date')
            ->limit(30)
            ->get();

        // Kalau tidak ada perjalanan yang bisa dikaitkan (belum ada perjalanan sama sekali,
        // atau semua perjalanan yang ada sudah pernah diisi laporannya), form tidak bisa diisi.
        if ($requests->isEmpty()) {
            return redirect()->route('drms.driver.expenses.index')
                ->withErrors('Tidak ada perjalanan yang bisa diisi laporan pengeluarannya. Perjalanan yang sudah diisi tidak bisa diisi ulang.');
        }

        return view('drms.drivers.expenses.create', compact('requests'));
    }

    /**
     * Simpan banyak entri pengeluaran sekaligus untuk SATU perjalanan,
     * dikelompokkan per kategori. Satu perjalanan hanya boleh diisi sekali.
     * Payload: items[toll][0][date|description|amount], items[parkir][0][...], dst.
     */
    public function store(Request $request)
    {
        $driver = $this->driverOrAbort();
        $validCategories = array_keys(ExpenseReport::CATEGORIES);

        $data = $request->validate([
            'request_id'                 => 'required|exists:drms_requests,id',
            'items'                      => 'required|array',
            'items.*'                   => 'array',
            'items.*.*.date'            => 'required|date',
            'items.*.*.description'     => 'nullable|string|max:255',
            'items.*.*.amount'          => 'required|numeric|min:1',
        ]);

        // Perjalanan wajib ada, milik driver ini, dan berstatus yang eligible.
        $owns = DriverRequest::where('id', $data['request_id'])
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['approved_admin', 'completed'])
            ->exists();
        if (!$owns) {
            return back()->withErrors('Perjalanan yang dipilih tidak valid. Laporan pengeluaran harus dikaitkan dengan perjalanan Anda sendiri.')->withInput();
        }

        // Satu perjalanan hanya boleh diisi satu kali. Kalau sudah pernah, tolak di sini juga
        // (bukan cuma disembunyikan dari dropdown) supaya tidak bisa dilewati lewat request langsung.
        $alreadyReported = ExpenseReport::where('driver_id', $driver->id)
            ->where('request_id', $data['request_id'])
            ->exists();
        if ($alreadyReported) {
            return back()->withErrors('Perjalanan ini sudah pernah diisi laporan pengeluarannya. Satu perjalanan hanya boleh satu laporan.')->withInput();
        }

        $count = 0;
        foreach ($data['items'] as $category => $rows) {
            if (!in_array($category, $validCategories, true)) {
                continue;
            }
            foreach ($rows as $row) {
                if (empty($row['amount']) || (float) $row['amount'] <= 0) {
                    continue;
                }
                ExpenseReport::create([
                    'driver_id'   => $driver->id,
                    'request_id'  => $data['request_id'],
                    'report_date' => $row['date'],
                    'category'    => $category,
                    'description' => $row['description'] ?? null,
                    'amount'      => $row['amount'],
                ]);
                $count++;
            }
        }

        if ($count === 0) {
            return back()->withErrors('Tidak ada entri yang diisi. Isi minimal satu pengeluaran.')->withInput();
        }

        return redirect()->route('drms.driver.expenses.index')
            ->with('success', "{$count} entri laporan pengeluaran berhasil disimpan.");
    }

    /**
     * Detail laporan pengeluaran untuk SATU perjalanan yang sudah diisi.
     * Diakses lewat /expenses/create/{driverRequest}, tetap di jalur "create"
     * supaya tidak perlu menu terpisah. Read-only, dengan tombol edit per entri
     * (kalau masih dalam masa edit) dan tombol download PDF khusus perjalanan ini.
     */
    public function detail(DriverRequest $driverRequest)
    {
        $driver = $this->driverOrAbort();
        if ($driverRequest->driver_id !== $driver->id) {
            abort(403, 'Anda tidak memiliki akses ke perjalanan ini.');
        }

        $items = ExpenseReport::where('driver_id', $driver->id)
            ->where('request_id', $driverRequest->id)
            ->orderBy('category')
            ->orderBy('report_date')
            ->get();

        if ($items->isEmpty()) {
            return redirect()->route('drms.driver.expenses.create')
                ->withErrors('Perjalanan ini belum ada laporan pengeluarannya.');
        }

        $totals = collect(ExpenseReport::CATEGORIES)->keys()->mapWithKeys(function ($cat) use ($items) {
            return [$cat => $items->where('category', $cat)->sum('amount')];
        });
        $grandTotal = $items->sum('amount');
        $isEditable = $items->every->is_editable;
        $editDeadline = optional($items->first())->edit_deadline;

        return view('drms.drivers.expenses.detail', compact('driverRequest', 'items', 'totals', 'grandTotal', 'isEditable', 'editDeadline'));
    }

    /**
     * Entri laporan pengeluaran tidak pernah bisa dihapus (kebijakan audit).
     * Method ini sengaja tidak melakukan delete apa pun, dijaga di controller
     * (bukan hanya disembunyikan di view) supaya tidak bisa dilewati lewat request langsung.
     */
    public function destroy(ExpenseReport $expense)
    {
        abort(403, 'Entri laporan pengeluaran tidak dapat dihapus.');
    }

    /**
     * Form edit satu entri pengeluaran. Hanya bisa diakses kalau entri
     * itu milik driver yang login DAN masih di dalam masa edit (maks. 10 hari
     * sejak perjalanan itu pertama kali diisi).
     */
    public function edit(ExpenseReport $expense)
    {
        $driver = $this->driverOrAbort();
        if ($expense->driver_id !== $driver->id) {
            abort(403, 'Anda tidak memiliki akses ke entri ini.');
        }
        if (!$expense->is_editable) {
            return redirect()->route('drms.driver.expenses.index')
                ->withErrors('Laporan perjalanan ini sudah lewat masa edit (maksimal ' . ExpenseReport::EDITABLE_DAYS . ' hari sejak diisi) dan terkunci.');
        }

        return view('drms.drivers.expenses.edit', compact('expense'));
    }

    /**
     * Simpan perubahan satu entri pengeluaran. Divalidasi ulang kepemilikan
     * dan masa edit di sini juga, supaya tidak bisa dilewati lewat request langsung.
     */
    public function update(Request $request, ExpenseReport $expense)
    {
        $driver = $this->driverOrAbort();
        if ($expense->driver_id !== $driver->id) {
            abort(403, 'Anda tidak memiliki akses ke entri ini.');
        }
        if (!$expense->is_editable) {
            return redirect()->route('drms.driver.expenses.index')
                ->withErrors('Laporan perjalanan ini sudah lewat masa edit (maksimal ' . ExpenseReport::EDITABLE_DAYS . ' hari sejak diisi) dan terkunci.');
        }

        $data = $request->validate([
            'category'    => 'required|in:' . implode(',', array_keys(ExpenseReport::CATEGORIES)),
            'report_date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'amount'      => 'required|numeric|min:1',
        ]);

        $expense->update($data);

        $redirectTo = $expense->request_id
            ? redirect()->route('drms.driver.expenses.detail', $expense->request_id)
            : redirect()->route('drms.driver.expenses.index');

        return $redirectTo->with('success', 'Entri pengeluaran berhasil diperbarui.');
    }

    /**
     * Cetak laporan pengeluaran SATU perjalanan ke PDF (1 perjalanan = 1 PDF).
     */
    public function pdf(DriverRequest $driverRequest)
    {
        $driver = $this->driverOrAbort();
        if ($driverRequest->driver_id !== $driver->id) {
            abort(403, 'Anda tidak memiliki akses ke perjalanan ini.');
        }

        $items = ExpenseReport::where('driver_id', $driver->id)
            ->where('request_id', $driverRequest->id)
            ->orderBy('category')
            ->orderBy('report_date')
            ->get();

        if ($items->isEmpty()) {
            abort(404, 'Belum ada laporan pengeluaran untuk perjalanan ini.');
        }

        $totals = collect(ExpenseReport::CATEGORIES)->keys()->mapWithKeys(function ($cat) use ($items) {
            return [$cat => $items->where('category', $cat)->sum('amount')];
        });
        $grandTotal = $items->sum('amount');

        $pdf = Pdf::loadView('drms.drivers.expenses.pdf', compact('driverRequest', 'items', 'totals', 'grandTotal', 'driver'))
            ->setPaper('a4', 'portrait');

        $filename = 'laporan-pengeluaran-' . \Illuminate\Support\Str::slug($driver->name) . '-trip-' . $driverRequest->id . '.pdf';

        return $pdf->download($filename);
    }
}