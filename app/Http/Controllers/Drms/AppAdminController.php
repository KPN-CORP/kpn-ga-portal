<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\Driver;
use App\Models\Drms\DriverRequest;
use App\Models\Drms\DriverSwapLog;
use App\Models\Drms\RequestLog;
use App\Models\Drms\Vehicle;
use App\Models\Drms\Voucher;
use App\Models\BisnisUnit;
use App\Models\User;  // <-- IMPORTANT: tambahkan ini
use App\Notifications\RequestApprovedAdminNotification;
use App\Notifications\RequestForwardedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AdminHistoryExport;
use App\Exports\OperationalReportExport;

class AppAdminController extends Controller
{
    /**
     * Tab status di halaman Approval Admin. Key = nilai ?status= di URL,
     * 'statuses' = status request di DB yang masuk ke tab itu.
     */
    private const STATUS_TABS = [
        'pending_l1'    => ['label' => 'Pending L1',    'statuses' => ['pending_l1']],
        'pending_admin' => ['label' => 'Pending Admin', 'statuses' => ['approved_l1']],
        'disetujui'     => ['label' => 'Disetujui',     'statuses' => ['approved_admin']],
        'selesai'       => ['label' => 'Selesai',       'statuses' => ['completed']],
        'ditolak'       => ['label' => 'Ditolak',       'statuses' => ['rejected_l1', 'rejected_admin']],
        'dibatalkan'    => ['label' => 'Dibatalkan',    'statuses' => ['cancelled']],
    ];

    /**
     * Query dasar untuk semua tab: cakupan BU/area + filter (search, tanggal, BU).
     * TIDAK membatasi status dan TIDAK lagi membatasi admin_id (admin melihat semua request di cakupannya).
     *  - Superadmin: semua BU; filter BU (?bu=) hanya berlaku untuk superadmin.
     *  - Admin biasa: BU-nya, dan area-areanya kalau ia memegang area (drms_admin_areas).
     *    Parameter ?bu= dari URL diabaikan untuk admin biasa.
     */
    private function baseQuery(Request $request, $user)
    {
        $profile = $user->drmsProfile;
        $query = DriverRequest::query();

        if ($user->isDrmsSuperAdmin()) {
            if ($request->filled('bu')) {
                $query->inAdminScope($request->bu, []);
            }
        } else {
            $query->inAdminScope($profile->business_unit_id ?? null, $profile ? $profile->adminAreaList() : []);
        }

        // Filter pencarian (request_no, pemohon, lokasi, tujuan, keperluan)
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('request_no', 'LIKE', $searchTerm)
                  ->orWhere('pickup_location', 'LIKE', $searchTerm)
                  ->orWhere('destination', 'LIKE', $searchTerm)
                  ->orWhere('purpose', 'LIKE', $searchTerm)
                  ->orWhereHas('requester', function ($q2) use ($searchTerm) {
                      $q2->where('name', 'LIKE', $searchTerm);
                  });
            });
        }

        // Filter rentang tanggal (usage_date)
        if ($request->filled('date_from')) {
            $query->whereDate('usage_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('usage_date', '<=', $request->date_to);
        }

        return $query;
    }

    /**
     * Halaman approval admin: tab status (Pending L1, Pending Admin, Disetujui, Selesai, Ditolak,
     * Dibatalkan) + tab Dipantau. Aksi (Proses/Tolak/Forward/Selesai/Ganti Driver/Pending)
     * tetap hanya muncul di tab yang relevan; tab lain read-only.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $profile = $user->drmsProfile;
        $isSuper = $user->isDrmsSuperAdmin();

        // Superadmin bisa akses semua tanpa profil DRMS
        if (!$isSuper && !$profile) {
            abort(403, 'Profil DRMS tidak ditemukan.');
        }

        $businessUnitId = $profile->business_unit_id ?? null;

        // ------------------- TAB STATUS -------------------
        $statusTabs = self::STATUS_TABS;
        $activeStatus = $request->query('status');
        // Kompatibilitas link lama (?status=approved_admin / rejected_admin / completed)
        $legacy = ['approved_admin' => 'disetujui', 'rejected_admin' => 'ditolak', 'completed' => 'selesai'];
        $activeStatus = $legacy[$activeStatus] ?? $activeStatus;
        if (!is_string($activeStatus) || !array_key_exists($activeStatus, $statusTabs)) {
            $activeStatus = 'pending_admin';
        }

        // Jumlah per tab (ikut filter search/tanggal/BU yang aktif), cukup 1 query.
        $rawCounts = $this->baseQuery($request, $user)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
        $tabCounts = [];
        foreach ($statusTabs as $key => $tab) {
            $tabCounts[$key] = (int) collect($tab['statuses'])->sum(fn ($st) => (int) ($rawCounts[$st] ?? 0));
        }

        $requests = $this->baseQuery($request, $user)
            ->with([
                'requester.drmsProfile.businessUnit', 'currentBusinessUnit', 'approverL1', 'admin',
                'driver', 'vehicle', 'voucher', 'vouchers.inputBusinessUnit', 'mergedInto', 'attachments',
            ])
            ->whereIn('status', $statusTabs[$activeStatus]['statuses'])
            ->latest()
            ->paginate(15)
            ->appends($request->query());

        // Tombol "Selesaikan" / "Pending" hanya relevan untuk request approved_admin.
        // Log perjalanan di-load sekali untuk semua baris (bukan 1 query per baris).
        $approvedIds = $requests->getCollection()->where('status', 'approved_admin')->pluck('id');
        $tripLogs = $approvedIds->isNotEmpty()
            ? \App\Models\Drms\TripLog::whereIn('request_id', $approvedIds)->get()->keyBy('request_id')
            : collect();

        foreach ($requests as $req) {
            $req->canComplete = false;
            $req->canReturnToPending = false;
            $req->completeBlockReason = null;

            if ($req->status !== 'approved_admin') {
                continue;
            }

            // Boleh dikembalikan ke Pending hanya selama jam perjalanan belum berakhir,
            // dan bukan request voucher (voucher tidak bisa dikembalikan).
            $req->canReturnToPending = !$req->hasEnded()
                && $req->transport_type !== 'voucher'
                && $req->vouchers->isEmpty();

            $log = $tripLogs->get($req->id);
            $logOk = $log && $log->is_submitted && $log->odometer_start !== null && $log->odometer_finish !== null;
            $usageDate = $req->usage_date instanceof \Carbon\Carbon
                ? $req->usage_date->copy()->startOfDay()
                : \Carbon\Carbon::parse($req->usage_date)->startOfDay();
            $dateOk = now()->startOfDay()->gte($usageDate);
            $req->canComplete = $logOk && $dateOk;
            $req->completeBlockReason = !$logOk
                ? 'Log Perjalanan belum diisi/disubmit driver'
                : (!$dateOk ? 'Belum tanggal jadwal (' . $usageDate->format('d M Y') . ')' : null);
        }

        // Export hanya tersedia untuk tab yang berisi status history (disetujui/selesai/ditolak).
        $canExport = (bool) array_intersect(
            $statusTabs[$activeStatus]['statuses'],
            ['approved_admin', 'rejected_admin', 'completed']
        );

        // ------------------- DIPANTAU: request BU SENDIRI yang di-forward ke BU LAIN -------------------
        // Begitu di-forward, request ini hilang dari $pendingRequests BU asal (karena scope
        // pendingForAdmin ikut current_business_unit_id), dan begitu selesai diproses BU tujuan
        // juga tidak akan masuk $historyRequests BU asal (karena admin_id jadi milik admin BU
        // tujuan). Akibatnya BU asal jadi buta total sama nasib request user-nya sendiri.
        // Section ini kasih visibilitas READ-ONLY (tanpa tombol aksi apapun) supaya BU asal
        // tetap bisa pantau: masih diproses BU tujuan, sudah disetujui/ditolak, atau malah
        // didiamkan (masih approved_l1 udah lama gak ada perubahan).
        //
        // Dipecah 2 supaya listnya gak numpuk panjang ke bawah kalau banyak yang di-forward:
        // - "Active"   : yang MASIH approved_l1 (belum ditindak BU tujuan) -- ini yang paling
        //                perlu dipantau, jumlahnya biasanya sedikit, jadi ditampilkan langsung
        //                semua tanpa pagination.
        // - "Resolved" : yang SUDAH selesai diproses BU tujuan (approved_admin/rejected_admin/
        //                completed) -- ini cuma arsip riwayat, jumlahnya bisa terus menumpuk
        //                seiring waktu, jadi dipaginate & disembunyikan di balik toggle
        //                "Tampilkan riwayat yang sudah selesai" biar gak bikin halaman panjang.
        $forwardedAwayRequests = collect();
        $forwardedResolvedRequests = null;
        $forwardedSummary = ['total' => 0, 'active' => 0, 'stale' => 0, 'resolved' => 0];

        if (!$user->isDrmsSuperAdmin() && $businessUnitId) {
            $forwardedBaseQuery = fn () => DriverRequest::where('original_business_unit_id', $businessUnitId)
                ->where(function ($q) use ($businessUnitId) {
                    $q->whereNull('current_business_unit_id')
                      ->orWhere('current_business_unit_id', '!=', $businessUnitId);
                });

            $forwardedAwayRequests = $forwardedBaseQuery()
                ->with(['requester', 'currentBusinessUnit', 'admin', 'forwardedBy'])
                ->where('status', 'approved_l1')
                ->latest('forwarded_at')
                ->get();

            // Tandai request yang kelihatan "didiamkan" BU tujuan: masih approved_l1
            // (belum ditindak sama sekali) padahal sudah di-forward lebih dari 2 hari lalu.
            foreach ($forwardedAwayRequests as $req) {
                $req->isStale = $req->forwarded_at && $req->forwarded_at->diffInDays(now()) >= 2;
            }
            // Yang paling lama didiamkan ditaruh paling atas (stable sort: urutkan tanggal dulu,
            // baru status stale, supaya di dalam grup stale/non-stale tetap urut terbaru dulu).
            $forwardedAwayRequests = $forwardedAwayRequests
                ->sortByDesc(fn ($r) => $r->forwarded_at)
                ->sortByDesc(fn ($r) => $r->isStale ? 1 : 0)
                ->values();

            $forwardedResolvedRequests = $forwardedBaseQuery()
                ->with(['requester', 'currentBusinessUnit', 'admin', 'forwardedBy'])
                ->whereIn('status', ['approved_admin', 'rejected_admin', 'completed'])
                ->latest('forwarded_at')
                ->paginate(10, ['*'], 'monitor_page')
                ->appends($request->query());

            $forwardedSummary = [
                'active'   => $forwardedAwayRequests->count(),
                'stale'    => $forwardedAwayRequests->where('isStale', true)->count(),
                'resolved' => $forwardedResolvedRequests->total(),
            ];
            $forwardedSummary['total'] = $forwardedSummary['active'] + $forwardedSummary['resolved'];
        }


        // Ambil semua business unit untuk dropdown forward modal
        $businessUnits = BisnisUnit::orderBy('nama_bisnis_unit')->get();

        // Daftar driver untuk dropdown "Ganti Driver" (status tersedia/lagi tugas).
        // Konflik jadwal tetap dicek ulang di server saat form swap disubmit.
        $availableDriversQuery = Driver::whereIn('status', ['available', 'on_trip']);
        if (!$user->isDrmsSuperAdmin() && !$user->hasDrmsAllBuAccess()) {
            $availableDriversQuery->where('business_unit_id', $businessUnitId);
        }
        $availableDrivers = $availableDriversQuery->orderBy('name')->get();

        // Daftar kendaraan untuk dropdown "Ganti Driver" (opsional ganti kendaraan juga).
        // Konflik jadwal tetap dicek ulang di server saat form swap disubmit.
        $availableVehiclesQuery = Vehicle::whereIn('status', ['available', 'in_use']);
        if (!$user->isDrmsSuperAdmin() && !$user->hasDrmsAllBuAccess()) {
            $availableVehiclesQuery->where('business_unit_id', $businessUnitId);
        }
        $availableVehicles = $availableVehiclesQuery->orderBy('plate_number')->get();

        return view('drms.approval.admin.index', compact(
            'requests', 'statusTabs', 'activeStatus', 'tabCounts', 'canExport', 'isSuper',
            'businessUnits', 'availableDrivers', 'availableVehicles',
            'forwardedAwayRequests', 'forwardedResolvedRequests', 'forwardedSummary'
        ));
    }

    /**
     * Export data history ke Excel (.xlsx) hanya jika ada filter yang aktif.
     * Cakupan data sama dengan halaman index (BU/area admin; superadmin semua / filter BU).
     * Hanya status history (disetujui, ditolak oleh admin, selesai) yang bisa diexport.
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        if (!$user->isDrmsSuperAdmin() && !$user->drmsProfile) {
            abort(403, 'Profil DRMS tidak ditemukan.');
        }

        // Cek apakah ada filter yang aktif
        $hasFilter = $request->filled('search')
            || $request->filled('status')
            || $request->filled('date_from')
            || $request->filled('date_to')
            || $request->filled('bu');

        if (!$hasFilter) {
            return redirect()->back()->withErrors('Harap terapkan filter terlebih dahulu sebelum download laporan.');
        }

        $exportable = ['approved_admin', 'rejected_admin', 'completed'];
        $tabKey = $request->query('status');
        $legacy = ['approved_admin' => 'disetujui', 'rejected_admin' => 'ditolak', 'completed' => 'selesai'];
        $tabKey = $legacy[$tabKey] ?? $tabKey;
        $statuses = (is_string($tabKey) && isset(self::STATUS_TABS[$tabKey]))
            ? array_values(array_intersect(self::STATUS_TABS[$tabKey]['statuses'], $exportable))
            : $exportable;

        if (empty($statuses)) {
            return redirect()->back()->withErrors('Tab ini tidak bisa diexport. Pilih tab Disetujui, Selesai, atau Ditolak.');
        }

        $exportQuery = $this->baseQuery($request, $user)
            ->with(['requester.drmsProfile', 'approverL1', 'admin', 'forwardedBy', 'driver', 'vehicle', 'voucher', 'vouchers'])
            ->whereIn('status', $statuses);

        // Export ke Excel
        $filename = 'laporan_approval_admin_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new AdminHistoryExport($exportQuery), $filename);
    }

    /**
     * Menampilkan form proses approval admin.
     * Driver dan kendaraan yang sedang on_trip/in_use tetapi tidak bentrok jadwal akan ditampilkan.
     */
    public function edit($id)
    {
        $driverRequest = DriverRequest::with('requester.drmsProfile')->findOrFail($id);
        $user = Auth::user();

        if (!$user->isDrmsSuperAdmin()) {
            $this->authorizeAdminAccess($driverRequest);
        }

        // Tentukan apakah admin boleh melihat semua BU
        $showAllBu = $user->hasDrmsAllBuAccess();

        // ----- QUERY DRIVER -----
        $driversQuery = Driver::where(function ($q) use ($driverRequest) {
            $q->where('status', 'available')
            ->orWhere(function ($sub) use ($driverRequest) {
                $sub->where('status', 'on_trip')
                    ->whereNotExists(function ($exists) use ($driverRequest) {
                        $exists->select(DB::raw(1))
                            ->from('drms_requests')
                            ->whereColumn('drms_requests.driver_id', 'drms_drivers.id')
                            ->whereIn('drms_requests.status', ['approved_admin', 'pending_l1', 'approved_l1'])
                            ->where('drms_requests.id', '!=', $driverRequest->id)
                            ->whereRaw("
                                CONCAT(drms_requests.usage_date, ' ', drms_requests.start_time) < ?
                                AND CONCAT(COALESCE(drms_requests.return_date, drms_requests.usage_date), ' ', COALESCE(drms_requests.return_time, drms_requests.end_time)) > ?
                            ", [
                                $driverRequest->return_date . ' ' . $driverRequest->end_time,
                                $driverRequest->usage_date . ' ' . $driverRequest->start_time
                            ]);
                    });
            });
        });

        if (!$showAllBu) {
            $businessUnitId = $driverRequest->current_business_unit_id ?? $driverRequest->requester->drmsProfile->business_unit_id;
            $driversQuery->where('business_unit_id', $businessUnitId);
        }

        $drivers = $driversQuery->get();

        // ----- QUERY KENDARAAN -----
        $vehiclesQuery = Vehicle::where(function ($q) use ($driverRequest) {
            $q->where('status', 'available')
            ->orWhere(function ($sub) use ($driverRequest) {
                $sub->where('status', 'in_use')
                    ->whereNotExists(function ($exists) use ($driverRequest) {
                        $exists->select(DB::raw(1))
                            ->from('drms_requests')
                            ->whereColumn('drms_requests.vehicle_id', 'drms_vehicles.id')
                            ->whereIn('drms_requests.status', ['approved_admin', 'pending_l1', 'approved_l1'])
                            ->where('drms_requests.id', '!=', $driverRequest->id)
                            ->whereRaw("
                                CONCAT(drms_requests.usage_date, ' ', drms_requests.start_time) < ?
                                AND CONCAT(COALESCE(drms_requests.return_date, drms_requests.usage_date), ' ', COALESCE(drms_requests.return_time, drms_requests.end_time)) > ?
                            ", [
                                $driverRequest->return_date . ' ' . $driverRequest->end_time,
                                $driverRequest->usage_date . ' ' . $driverRequest->start_time
                            ]);
                    });
            });
        });

        if (!$showAllBu) {
            $vehiclesQuery->where('business_unit_id', $businessUnitId);
        }

        $vehicles = $vehiclesQuery->get();

        // ----- QUERY VOUCHER (opsional, ikuti logika yang sama) -----
        // Voucher yang sudah expired tidak boleh ditawarkan lagi untuk request baru.
        // Pengecualian: voucher yang MEMANG sudah terpasang di request ini tetap ditampilkan,
        // supaya nilai yang sudah tersimpan tidak hilang begitu saja dari pilihan saat form dibuka.
        $attachedVoucherIds = $driverRequest->vouchers()->pluck('drms_vouchers.id')->toArray();
        $vouchersQuery = Voucher::with(['businessUnit', 'inputBusinessUnit'])
            ->where(function ($q) use ($attachedVoucherIds) {
                $q->where('status', 'available')
                  ->where(function ($sub) {
                      $sub->whereNull('expired_at')
                          ->orWhereDate('expired_at', '>=', now()->toDateString());
                  });
                if (!empty($attachedVoucherIds)) {
                    $q->orWhereIn('id', $attachedVoucherIds);
                }
            });
        // BU + AREA: voucher harus satu area dengan pemohon (lihat Voucher::scopeAssignableTo).
        // Superadmin tetap melihat semua. Voucher yang sudah terpasang di request ini
        // tetap ikut tampil lewat $attachedVoucherIds di atas.
        $vouchersQuery->where(function ($outer) use ($driverRequest, $user, $showAllBu, $attachedVoucherIds) {
            $outer->where(function ($q) use ($driverRequest, $user, $showAllBu) {
                $q->assignableTo($driverRequest, $user, $showAllBu);
            });
            if (!empty($attachedVoucherIds)) {
                $outer->orWhereIn('id', $attachedVoucherIds);
            }
        });
        $vouchers = $vouchersQuery->get();

        $allBusinessUnits = BisnisUnit::orderBy('nama_bisnis_unit')->get();

        // ----- QUERY TRIP YANG BISA DIGABUNG -----
        // Hanya request yang sudah "jalan" (approved_admin), sudah punya driver+kendaraan,
        // dan dia sendiri BUKAN request yang sedang menumpang ke trip lain (mencegah gabung berlapis).
        $mergeableQuery = DriverRequest::with(['requester.drmsProfile', 'driver', 'vehicle'])
            ->where('id', '!=', $driverRequest->id)
            ->where('status', 'approved_admin')
            ->whereNotNull('driver_id')
            ->whereNull('merged_into_id')
            ->where('usage_date', $driverRequest->usage_date);
        if (!$showAllBu) {
            // `current_business_unit_id` cuma terisi kalau trip pernah di-forward ke BU lain;
            // untuk trip normal kolom ini NULL, jadi kita fallback ke BU asli requester-nya
            // (sama seperti cara $businessUnitId dihitung di atas) supaya trip yang memang
            // satu BU tetap muncul sebagai opsi gabung, bukan cuma trip yang pernah di-forward.
            $mergeableQuery->where(function ($q) use ($businessUnitId) {
                $q->where('current_business_unit_id', $businessUnitId)
                  ->orWhere(function ($sub) use ($businessUnitId) {
                      $sub->whereNull('current_business_unit_id')
                          ->whereHas('requester.drmsProfile', function ($p) use ($businessUnitId) {
                              $p->where('business_unit_id', $businessUnitId);
                          });
                  });
            });
        }
        $mergeableRequests = $mergeableQuery->orderBy('start_time')->get();

        return view('drms.approval.admin.edit', compact(
            'driverRequest', 'drivers', 'vehicles', 'vouchers', 'allBusinessUnits', 'mergeableRequests', 'attachedVoucherIds'
        ));
    }

    /**
     * Memproses approval admin (setujui).
     */
    public function update(Request $request, $id)
    {
        $driverRequest = DriverRequest::findOrFail($id);
        $user = Auth::user();

        if (!$user->isDrmsSuperAdmin()) {
            $this->authorizeAdminAccess($driverRequest);
        }

        $data = $request->validate([
            'transport_type'    => 'required|in:company_driver,voucher,rental,merge',
            'driver_id'         => 'nullable|required_if:transport_type,company_driver|exists:drms_drivers,id',
            'vehicle_id'        => 'nullable|required_if:transport_type,company_driver|exists:drms_vehicles,id',
            'voucher_ids'       => 'nullable|array',
            'voucher_ids.*'     => 'exists:drms_vouchers,id',
            'merged_into_id'    => 'nullable|required_if:transport_type,merge|exists:drms_requests,id',
            'keterangan'        => 'nullable|string',
        ]);

        // Voucher sekarang bisa lebih dari 1 — minimal 1 voucher wajib dipilih
        // kalau transport_type = voucher.
        if ($data['transport_type'] === 'voucher' && empty($data['voucher_ids'])) {
            return back()->withErrors('Pilih minimal 1 voucher.')->withInput();
        }

        // Validasi BU + AREA di server: voucher baru yang dipilih harus milik BU dan area yang
        // sama dengan pemohon (bukan cuma lolos dari dropdown). Voucher yang MEMANG sudah
        // terpasang di request ini tidak dicek ulang.
        if ($data['transport_type'] === 'voucher' && !empty($data['voucher_ids'])) {
            $alreadyAttached = $driverRequest->vouchers()->pluck('drms_vouchers.id')->toArray();
            $newIds = array_values(array_diff(array_unique($data['voucher_ids']), $alreadyAttached));
            if (!empty($newIds)) {
                $allowedCount = Voucher::whereIn('id', $newIds)
                    ->assignableTo($driverRequest, $user, $user->hasDrmsAllBuAccess())
                    ->count();
                if ($allowedCount !== count($newIds)) {
                    return back()->withErrors('Ada voucher yang bukan milik BU/area pemohon ini.')->withInput();
                }
            }
        }

        // Validasi ulang di server: voucher yang sudah expired tidak boleh diberikan,
        // meskipun opsinya lolos dari dropdown (mis. dikirim manual lewat request langsung).
        // Pakai accessor is_expired dari model Voucher supaya logic "tidak expired di
        // hari-H, baru besoknya" konsisten di semua tempat (tidak dihitung ulang di sini).
        if ($data['transport_type'] === 'voucher' && !empty($data['voucher_ids'])) {
            $expiredVouchers = Voucher::whereIn('id', $data['voucher_ids'])->get()->filter(fn($v) => $v->is_expired);
            if ($expiredVouchers->isNotEmpty()) {
                return back()->withErrors('Voucher berikut sudah kadaluarsa: ' . $expiredVouchers->pluck('code')->implode(', ') . '. Silakan pilih voucher lain.')->withInput();
            }
        }

        // Validasi ulang di server untuk penggabungan trip: trip induk harus benar-benar
        // sudah approved_admin, punya driver, dan bukan dia sendiri sedang menumpang ke trip lain.
        $mergeTarget = null;
        if ($data['transport_type'] === 'merge') {
            $mergeTarget = DriverRequest::find($data['merged_into_id']);
            if (!$mergeTarget
                || $mergeTarget->id === $driverRequest->id
                || $mergeTarget->status !== 'approved_admin'
                || !$mergeTarget->driver_id
                || $mergeTarget->merged_into_id) {
                return back()->withErrors('Trip yang dipilih untuk digabung sudah tidak valid. Silakan pilih ulang.')->withInput();
            }
        }

        $oldDriverId  = $driverRequest->driver_id;
        $oldVehicleId = $driverRequest->vehicle_id;
        $oldVoucherIds = $driverRequest->vouchers()->pluck('drms_vouchers.id')->toArray();

        if ($data['transport_type'] === 'company_driver') {
            if (!$driverRequest->end_time) {
                return back()->withErrors('Request ini tidak memiliki perkiraan jam selesai.');
            }

            $startDate = $driverRequest->usage_date->format('Y-m-d');
            $startTime = $driverRequest->start_time;
            if ($driverRequest->trip_type === 'round_trip' && $driverRequest->return_date) {
                $endDate = $driverRequest->return_date->format('Y-m-d');
                $endTime = $driverRequest->return_time ?? $driverRequest->end_time;
            } else {
                $endDate = $startDate;
                $endTime = $driverRequest->end_time;
            }

            // Cek konflik driver
            $driverConflict = DriverRequest::overlappingPeriod(
                'driver_id',
                $data['driver_id'],
                $startDate,
                $startTime,
                $endDate,
                $endTime,
                $driverRequest->id
            )->exists();

            if ($driverConflict) {
                return back()->withErrors('Driver sudah ditugaskan pada rentang waktu tersebut.');
            }

            // Cek konflik kendaraan
            $vehicleConflict = DriverRequest::overlappingPeriod(
                'vehicle_id',
                $data['vehicle_id'],
                $startDate,
                $startTime,
                $endDate,
                $endTime,
                $driverRequest->id
            )->exists();

            if ($vehicleConflict) {
                return back()->withErrors('Kendaraan sudah digunakan pada rentang waktu tersebut.');
            }
        }

        DB::beginTransaction();
        try {
            if ($data['transport_type'] === 'merge') {
                // Menumpang ke trip lain: driver & kendaraan disalin dari trip induk,
                // TIDAK mengubah status driver/kendaraan (mereka sudah on_trip/in_use dari induknya)
                // dan TIDAK menyentuh voucher (voucher tetap milik trip induk saja).
                $driverRequest->update([
                    'transport_type'    => 'company_driver',
                    'driver_id'         => $mergeTarget->driver_id,
                    'vehicle_id'        => $mergeTarget->vehicle_id,
                    'voucher_id'        => null,
                    'merged_into_id'    => $mergeTarget->id,
                    'admin_id'          => Auth::id(),
                    'status'            => 'approved_admin',
                    'approved_admin_at' => now(),
                    'rejection_reason'  => $data['keterangan'] ?? null,
                ]);

                // Kalau request ini sebelumnya punya driver/kendaraan/voucher sendiri
                // (mis. sedang diedit ulang dari company_driver ke merge), lepas itu dulu.
                if ($oldDriverId && $oldDriverId != $mergeTarget->driver_id) {
                    Driver::where('id', $oldDriverId)->update(['status' => 'available']);
                }
                if ($oldVehicleId && $oldVehicleId != $mergeTarget->vehicle_id) {
                    Vehicle::where('id', $oldVehicleId)->update(['status' => 'available']);
                }
                if ($oldVoucherIds) {
                    Voucher::whereIn('id', $oldVoucherIds)->update(['status' => 'available']);
                }
                $driverRequest->vouchers()->sync([]);

                $driverRequest->requester->notify(new RequestApprovedAdminNotification($driverRequest));

                DB::commit();
                return redirect()->route('drms.approval.admin.index')
                    ->with('success', 'Request berhasil digabung ke trip ' . ($mergeTarget->request_no ?? ('#' . $mergeTarget->id)) . '.');
            }

            $newVoucherIds = $data['transport_type'] === 'voucher' ? ($data['voucher_ids'] ?? []) : [];

            // Request voucher tidak punya driver/log perjalanan yang bisa diselesaikan,
            // jadi begitu disetujui statusnya otomatis langsung "Selesai" (completed).
            $isVoucher = $data['transport_type'] === 'voucher';

            $driverRequest->update([
                'transport_type'    => $data['transport_type'],
                'driver_id'         => $data['driver_id'] ?? null,
                'vehicle_id'        => $data['vehicle_id'] ?? null,
                'voucher_id'        => $newVoucherIds[0] ?? null, // voucher utama, untuk kompatibilitas tampilan lama
                'merged_into_id'    => null,
                'admin_id'          => Auth::id(),
                'status'            => $isVoucher ? 'completed' : 'approved_admin',
                'approved_admin_at' => now(),
                'completed_at'      => $isVoucher ? now() : null,
                'rejection_reason'  => $data['keterangan'] ?? null,
            ]);

            $driverRequest->vouchers()->sync($newVoucherIds);

            // Update status lama
            if ($oldDriverId && $oldDriverId != ($data['driver_id'] ?? null)) {
                Driver::where('id', $oldDriverId)->update(['status' => 'available']);
            }
            if ($oldVehicleId && $oldVehicleId != ($data['vehicle_id'] ?? null)) {
                Vehicle::where('id', $oldVehicleId)->update(['status' => 'available']);
            }
            // Voucher lama yang tidak lagi dipilih -> kembalikan status jadi available
            $releasedVoucherIds = array_diff($oldVoucherIds, $newVoucherIds);
            if (!empty($releasedVoucherIds)) {
                Voucher::whereIn('id', $releasedVoucherIds)->update(['status' => 'available']);
            }

            // Update status baru
            if (!empty($data['driver_id'])) {
                Driver::where('id', $data['driver_id'])->update(['status' => 'on_trip']);
            }
            if (!empty($data['vehicle_id'])) {
                Vehicle::where('id', $data['vehicle_id'])->update(['status' => 'in_use']);
            }
            if (!empty($newVoucherIds)) {
                Voucher::whereIn('id', $newVoucherIds)->update(['status' => 'used']);
            }

            // Kirim notifikasi ke pemohon
            $driverRequest->requester->notify(new RequestApprovedAdminNotification($driverRequest));

            DB::commit();
            return redirect()->route('drms.approval.admin.index')
                ->with('success', 'Request berhasil diproses.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Menolak permintaan oleh admin.
     */
    public function reject(Request $request, $id)
    {
        $request->validate(['rejection_reason' => 'required|string']);

        $driverRequest = DriverRequest::findOrFail($id);
        $user = Auth::user();

        if (!$user->isDrmsSuperAdmin()) {
            $this->authorizeAdminAccess($driverRequest);
        }

        if ($driverRequest->status !== 'approved_l1') {
            return back()->withErrors('Permintaan tidak dapat ditolak karena status sudah diproses.');
        }

        $oldDriverId  = $driverRequest->driver_id;
        $oldVehicleId = $driverRequest->vehicle_id;
        $oldVoucherIds = $driverRequest->vouchers()->pluck('drms_vouchers.id')->toArray();

        DB::beginTransaction();
        try {
            $driverRequest->update([
                'status'           => 'rejected_admin',
                'rejection_reason' => $request->rejection_reason,
                'admin_id'         => Auth::id(),
                'approved_admin_at'=> null,
                'transport_type'   => null,
                'driver_id'        => null,
                'vehicle_id'       => null,
                'voucher_id'       => null,
            ]);

            if ($oldDriverId) Driver::where('id', $oldDriverId)->update(['status' => 'available']);
            if ($oldVehicleId) Vehicle::where('id', $oldVehicleId)->update(['status' => 'available']);
            if (!empty($oldVoucherIds)) Voucher::whereIn('id', $oldVoucherIds)->update(['status' => 'available']);
            $driverRequest->vouchers()->sync([]);

            DB::commit();
            return redirect()->route('drms.approval.admin.index')
                ->with('success', 'Permintaan driver ditolak.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Menandai permintaan sebagai selesai oleh admin.
     */
    public function complete(DriverRequest $driverRequest)
    {
        $user = Auth::user();

        if (!$user->isDrmsSuperAdmin()) {
            $this->authorizeAdminAccess($driverRequest);
        }

        if ($driverRequest->status !== 'approved_admin') {
            return back()->withErrors('Hanya permintaan dengan status Disetujui yang bisa diselesaikan.');
        }

        // Request yang menumpang ke trip lain tidak boleh diselesaikan sendiri-sendiri —
        // itu bisa salah membebaskan driver/kendaraan padahal trip induknya masih jalan.
        // Harus diselesaikan lewat trip induknya, yang otomatis akan cascade ke sini juga.
        if ($driverRequest->merged_into_id) {
            return back()->withErrors('Perjalanan ini digabung dengan trip lain. Selesaikan lewat trip induknya (ID #' . $driverRequest->merged_into_id . ').');
        }

        // 1) Tidak boleh selesai sebelum Log Perjalanan diisi & disubmit driver.
        $log = \App\Models\Drms\TripLog::where('request_id', $driverRequest->id)->first();
        if (!$log || !$log->is_submitted || $log->odometer_start === null || $log->odometer_finish === null) {
            return back()->withErrors('Permintaan belum bisa diselesaikan karena Log Perjalanan (odometer start/finish) belum diisi & disubmit oleh driver.');
        }

        // 2) Tidak boleh selesai sebelum tanggal jadwalnya (usage_date) tiba.
        $usageDate = $driverRequest->usage_date instanceof \Carbon\Carbon
            ? $driverRequest->usage_date->copy()->startOfDay()
            : \Carbon\Carbon::parse($driverRequest->usage_date)->startOfDay();
        if (now()->startOfDay()->lt($usageDate)) {
            return back()->withErrors('Permintaan ini terjadwal pada ' . $usageDate->format('d M Y') . '. Belum bisa diselesaikan sebelum tanggal tersebut.');
        }

        DB::beginTransaction();
        try {
            $completedAt = now();
            $driverRequest->update([
                'status' => 'completed',
                'completed_at' => $completedAt,
            ]);

            // Kalau request ini adalah INDUK dari trip gabungan, ikut selesaikan
            // semua request "penumpang" yang menumpang ke trip ini.
            $passengers = DriverRequest::where('merged_into_id', $driverRequest->id)
                ->where('status', '!=', 'completed')
                ->get();
            foreach ($passengers as $passenger) {
                $passenger->update([
                    'status' => 'completed',
                    'completed_at' => $completedAt,
                ]);
            }

            if ($driverRequest->driver_id) {
                Driver::where('id', $driverRequest->driver_id)->update(['status' => 'available']);
            }
            if ($driverRequest->vehicle_id) {
                Vehicle::where('id', $driverRequest->vehicle_id)->update(['status' => 'available']);
            }

            DB::commit();
            return redirect()->route('drms.approval.admin.index', ['status' => 'selesai'])
                ->with('success', 'Permintaan berhasil diselesaikan.' . ($passengers->count() ? " ({$passengers->count()} request yang menumpang ikut diselesaikan.)" : ''));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal menyelesaikan permintaan.');
        }
    }

    /**
     * Mengganti (swap/tukar) driver — dan opsional kendaraan — pada request yang sudah
     * disetujui admin (approved_admin) tapi belum selesai. Setiap pergantian dicatat
     * ke tabel drms_driver_swap_logs sebagai riwayat.
     */
    public function swapDriver(Request $request, $id)
    {
        $driverRequest = DriverRequest::findOrFail($id);
        $user = Auth::user();

        if (!$user->isDrmsSuperAdmin()) {
            $this->authorizeAdminAccess($driverRequest);
        }

        if ($driverRequest->status !== 'approved_admin') {
            return back()->withErrors('Driver hanya bisa diganti untuk permintaan berstatus Disetujui (approved_admin) dan belum selesai.');
        }

        if ($driverRequest->merged_into_id) {
            return back()->withErrors('Perjalanan ini menumpang ke trip lain. Ganti driver lewat trip induknya (ID #' . $driverRequest->merged_into_id . ').');
        }

        $data = $request->validate([
            'new_driver_id'  => 'nullable|exists:drms_drivers,id',
            'new_vehicle_id' => 'nullable|exists:drms_vehicles,id',
            'reason'         => 'nullable|string|max:500',
        ]);

        $oldDriverId  = $driverRequest->driver_id;
        $oldVehicleId = $driverRequest->vehicle_id;
        $newDriverId  = $data['new_driver_id'] ?? $oldDriverId;
        $newVehicleId = $data['new_vehicle_id'] ?? $oldVehicleId;

        $driverChanged  = $newDriverId != $oldDriverId;
        $vehicleChanged = $newVehicleId != $oldVehicleId;

        if (!$driverChanged && !$vehicleChanged) {
            return back()->withErrors('Tidak ada perubahan. Pilih driver dan/atau kendaraan pengganti, atau biarkan salah satunya kosong untuk tetap memakai yang sekarang.');
        }

        if (!$driverRequest->end_time) {
            return back()->withErrors('Request ini tidak memiliki perkiraan jam selesai.');
        }

        $startDate = $driverRequest->usage_date->format('Y-m-d');
        $startTime = $driverRequest->start_time;
        if ($driverRequest->trip_type === 'round_trip' && $driverRequest->return_date) {
            $endDate = $driverRequest->return_date->format('Y-m-d');
            $endTime = $driverRequest->return_time ?? $driverRequest->end_time;
        } else {
            $endDate = $startDate;
            $endTime = $driverRequest->end_time;
        }

        // Cek konflik jadwal driver pengganti (hanya kalau drivernya memang diganti).
        if ($driverChanged) {
            $driverConflict = DriverRequest::overlappingPeriod(
                'driver_id', $newDriverId, $startDate, $startTime, $endDate, $endTime, $driverRequest->id
            )->exists();
            if ($driverConflict) {
                return back()->withErrors('Driver pengganti sudah ditugaskan pada rentang waktu tersebut.');
            }
        }

        // Cek konflik jadwal kendaraan pengganti (hanya kalau kendaraannya memang diganti).
        if ($vehicleChanged) {
            $vehicleConflict = DriverRequest::overlappingPeriod(
                'vehicle_id', $newVehicleId, $startDate, $startTime, $endDate, $endTime, $driverRequest->id
            )->exists();
            if ($vehicleConflict) {
                return back()->withErrors('Kendaraan pengganti sudah digunakan pada rentang waktu tersebut.');
            }
        }

        DB::beginTransaction();
        try {
            DriverSwapLog::create([
                'request_id'         => $driverRequest->id,
                'old_driver_id'      => $oldDriverId,
                'new_driver_id'      => $driverChanged ? $newDriverId : null,
                'old_vehicle_id'     => $oldVehicleId,
                'new_vehicle_id'     => $vehicleChanged ? $newVehicleId : null,
                'reason'             => $data['reason'] ?? null,
                'changed_by_user_id' => $user->id,
            ]);

            $driverRequest->update([
                'driver_id'  => $newDriverId,
                'vehicle_id' => $newVehicleId,
            ]);

            if ($driverChanged) {
                if ($oldDriverId) {
                    Driver::where('id', $oldDriverId)->update(['status' => 'available']);
                }
                Driver::where('id', $newDriverId)->update(['status' => 'on_trip']);
            }

            if ($vehicleChanged) {
                if ($oldVehicleId) Vehicle::where('id', $oldVehicleId)->update(['status' => 'available']);
                if ($newVehicleId) Vehicle::where('id', $newVehicleId)->update(['status' => 'in_use']);
            }

            $successMessage = $driverChanged && $vehicleChanged
                ? 'Driver dan kendaraan berhasil diganti.'
                : ($driverChanged ? 'Driver berhasil diganti.' : 'Kendaraan berhasil diganti.');

            DB::commit();
            return back()->with('success', $successMessage);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal mengganti driver: ' . $e->getMessage());
        }
    }

    /**
     * Mengembalikan request yang sudah disetujui (approved_admin) ke Pending Admin (approved_l1)
     * supaya bisa diproses ulang. Hanya boleh selama jam perjalanan belum berakhir.
     * Driver/kendaraan/voucher dilepas, dan aksinya dicatat di drms_request_logs.
     */
    public function returnToPending(Request $request, $id)
    {
        $request->validate(['note' => 'required|string|max:500']);

        $driverRequest = DriverRequest::with('vouchers')->findOrFail($id);
        $user = Auth::user();

        if (!$user->isDrmsSuperAdmin()) {
            $this->authorizeAdminAccess($driverRequest);
        }

        if ($driverRequest->status !== 'approved_admin') {
            return back()->withErrors('Hanya permintaan berstatus Disetujui yang bisa dikembalikan ke Pending.');
        }

        if ($driverRequest->transport_type === 'voucher' || $driverRequest->vouchers->isNotEmpty()) {
            return back()->withErrors('Permintaan dengan voucher tidak bisa dikembalikan ke Pending.');
        }

        if ($driverRequest->hasEnded()) {
            return back()->withErrors('Jam perjalanan sudah berakhir (' . $driverRequest->scheduledEnd()->format('d M Y H:i') . '). Permintaan ini tidak bisa diubah lagi.');
        }

        if (DriverRequest::where('merged_into_id', $driverRequest->id)->exists()) {
            return back()->withErrors('Trip ini punya penumpang gabungan. Lepaskan/proses penumpangnya dulu sebelum dikembalikan ke Pending.');
        }

        // Request yang menumpang ke trip lain hanya melepas ikatannya; driver & kendaraan
        // milik trip induk, jadi statusnya tidak boleh disentuh.
        $isPassenger   = (bool) $driverRequest->merged_into_id;
        $oldDriverId   = $driverRequest->driver_id;
        $oldVehicleId  = $driverRequest->vehicle_id;
        $oldVoucherIds = $driverRequest->vouchers->pluck('id')->toArray();

        $detail = 'Jenis: ' . ($driverRequest->transport_type ?? '-')
            . ' | Driver: ' . ($oldDriverId ? (optional(Driver::find($oldDriverId))->name ?? ('#' . $oldDriverId)) : '-')
            . ' | Kendaraan: ' . ($oldVehicleId ? (optional(Vehicle::find($oldVehicleId))->plate_number ?? ('#' . $oldVehicleId)) : '-')
            . ' | Voucher: ' . ($driverRequest->vouchers->isNotEmpty() ? $driverRequest->vouchers->pluck('code')->implode(', ') : '-')
            . ($isPassenger ? ' | Menumpang ke request #' . $driverRequest->merged_into_id : '');

        DB::beginTransaction();
        try {
            RequestLog::create([
                'request_id'  => $driverRequest->id,
                'action'      => 'return_to_pending',
                'from_status' => 'approved_admin',
                'to_status'   => 'approved_l1',
                'note'        => $request->note,
                'detail'      => $detail,
                'user_id'     => $user->id,
            ]);

            $driverRequest->update([
                'status'              => 'approved_l1',
                'admin_id'            => null,
                'approved_admin_at'   => null,
                'rejection_reason'    => null,
                'transport_type'      => null,
                'driver_id'           => null,
                'vehicle_id'          => null,
                'voucher_id'          => null,
                'merged_into_id'      => null,
                'email_approved_sent' => 0, // approve ulang nanti -> email disetujui terkirim lagi
            ]);
            $driverRequest->vouchers()->sync([]);

            if (!$isPassenger) {
                if ($oldDriverId)  Driver::where('id', $oldDriverId)->update(['status' => 'available']);
                if ($oldVehicleId) Vehicle::where('id', $oldVehicleId)->update(['status' => 'available']);
            }
            if (!empty($oldVoucherIds)) {
                Voucher::whereIn('id', $oldVoucherIds)->update(['status' => 'available']);
            }

            DB::commit();
            return redirect()->route('drms.approval.admin.index')
                ->with('success', 'Permintaan dikembalikan ke Pending Admin.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal mengembalikan ke Pending: ' . $e->getMessage());
        }
    }

    /**
     * Mengalihkan permintaan ke business unit lain.
     */
    public function forward(Request $request, $id)
    {
        $driverRequest = DriverRequest::findOrFail($id);
        $user = Auth::user();

        if (!$user->isDrmsSuperAdmin()) {
            $this->authorizeAdminAccess($driverRequest);
        }

        if ($driverRequest->status !== 'approved_l1') {
            return back()->withErrors('Hanya permintaan dengan status approved_l1 yang bisa dialihkan.');
        }

        $request->validate([
            'target_business_unit_id' => 'required|exists:tb_bisnis_unit,id_bisnis_unit',
            'note' => 'nullable|string|max:500',
        ]);

        $targetBuId = $request->target_business_unit_id;

        // Cek apakah target BU memiliki admin aktif
        $targetAdmins = User::whereHas('drmsProfile', function ($q) use ($targetBuId) {
            $q->where('is_drms_admin', true)
              ->where('business_unit_id', $targetBuId);
        })->get();

        if ($targetAdmins->isEmpty()) {
            return back()->withErrors('Target Business Unit tidak memiliki admin aktif.');
        }

        DB::beginTransaction();
        try {
            // Simpan data lama untuk dikembalikan jika perlu
            $oldDriverId = $driverRequest->driver_id;
            $oldVehicleId = $driverRequest->vehicle_id;
            $oldVoucherIds = $driverRequest->vouchers()->pluck('drms_vouchers.id')->toArray();

            // Set current business unit
            if (!$driverRequest->original_business_unit_id) {
                $driverRequest->original_business_unit_id = $driverRequest->requester->drmsProfile->business_unit_id;
            }
            $driverRequest->current_business_unit_id = $targetBuId;
            $driverRequest->forwarded_by_user_id = $user->id;
            $driverRequest->forwarded_at = now();

            // Reset data assignment karena akan diproses ulang oleh admin BU tujuan
            $driverRequest->admin_id = null;
            $driverRequest->driver_id = null;
            $driverRequest->vehicle_id = null;
            $driverRequest->voucher_id = null;
            $driverRequest->transport_type = null;
            $driverRequest->status = 'approved_l1';
            $driverRequest->approved_admin_at = null;
            $driverRequest->rejection_reason = null;

            $driverRequest->save();

            // Update status driver/kendaraan/voucher lama menjadi available
            if ($oldDriverId) Driver::where('id', $oldDriverId)->update(['status' => 'available']);
            if ($oldVehicleId) Vehicle::where('id', $oldVehicleId)->update(['status' => 'available']);
            if (!empty($oldVoucherIds)) Voucher::whereIn('id', $oldVoucherIds)->update(['status' => 'available']);
            $driverRequest->vouchers()->sync([]);

            // Kirim notifikasi ke semua admin BU tujuan
            Notification::send($targetAdmins, new RequestForwardedNotification($driverRequest, $user, $request->note));

            DB::commit();

            return redirect()->route('drms.approval.admin.index')
                ->with('success', "Permintaan berhasil dialihkan ke Business Unit tujuan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal mengalihkan permintaan: ' . $e->getMessage());
        }
    }

    /**
     * Export dashboard data ke Excel
     */
    public function exportDashboard(Request $request)
    {
        $user = Auth::user();
        $businessUnitId = $this->getBusinessUnitId($user);
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        // Ambil data yang sama seperti di dashboard
        $stats = $this->getOperationalStats($businessUnitId, $month, $year);
        $chartData = $this->getMonthlyChartData($businessUnitId);
        $efficiencyData = $this->getEfficiencyData($businessUnitId);
        $transportDistribution = $this->getTransportDistribution($businessUnitId);

        // Buat export ke Excel
        return Excel::download(new OperationalDashboardExport($stats, $chartData, $efficiencyData, $transportDistribution), 'laporan_operasional_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Helper untuk mengecek apakah admin memiliki akses terhadap request tertentu.
     * Berdasarkan current_business_unit_id atau BU requester.
     *
     * @param DriverRequest $driverRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function authorizeAdminAccess(DriverRequest $driverRequest)
    {
        $user = Auth::user();
        $adminProfile = $user->drmsProfile;

        if (!$adminProfile) {
            abort(403, 'Profil admin tidak ditemukan.');
        }

        $requesterBu = $driverRequest->requester->drmsProfile->business_unit_id ?? null;
        $currentBu = $driverRequest->current_business_unit_id ?? $requesterBu;

        if ($adminProfile->business_unit_id != $currentBu) {
            abort(403, 'Anda tidak memiliki akses ke permintaan ini.');
        }

        // Admin ber-area hanya boleh memproses request dari area-area yang dipegangnya
        // (drms_admin_areas); admin tanpa area = lintas area dalam BU-nya. Request hasil forward
        // (current_business_unit_id terisi) tidak dicek area, sama seperti scope pendingForAdmin.
        if (!empty($adminProfile->adminAreaList()) && empty($driverRequest->current_business_unit_id)) {
            $requesterArea = $driverRequest->requester->drmsProfile->area ?? null;
            if (!$adminProfile->holdsArea($requesterArea)) {
                abort(403, 'Permintaan ini milik area lain.');
            }
        }
    }
}