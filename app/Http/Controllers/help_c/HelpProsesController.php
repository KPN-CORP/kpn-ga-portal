<?php

namespace App\Http\Controllers\help_c;

use App\Http\Controllers\Controller;

use App\Models\help_m\HelpTiket;
use App\Models\help_m\HelpKategori;
use App\Models\BisnisUnit;
use App\Models\help_m\HelpLogStatus;
use App\Models\help_m\HelpKomentar;
use App\Models\help_m\HelpLampiran;
use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HelpProsesController extends Controller
{
    const LAMPIRAN_TIPE = [
        'INITIAL' => 'INITIAL',
        'FOLLOW_UP' => 'FOLLOW_UP',
        'COMPLETION' => 'COMPLETION'
    ];

    const STATUS_TIKET = [
        'OPEN' => 'OPEN',
        'ON_PROCESS' => 'ON_PROCESS',
        'WAITING' => 'WAITING',
        'DONE' => 'DONE',
        'CLOSED' => 'CLOSED',
        'HELP_GA_CORP' => 'HELP_GA_CORP',
    ];

    /**
     * Display a listing of tickets for processing.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $bisnisUnits = BisnisUnit::orderBy('nama_bisnis_unit')->get();
        $kategori = HelpKategori::where('aktif', true)->orderBy('nama')->get();

        $query = HelpTiket::with([
            'kategori',
            'bisnisUnit',
            'pelapor.user',
            'ditugaskanKe.user',
            'logStatus',
            'vendorProses'
        ]);

        $accessibleUnits = $user->getAccessibleBusinessUnits();

        if (!empty($accessibleUnits)) {
            $query->whereIn('bisnis_unit_id', $accessibleUnits);
        } else {
            if ($user->pelanggan) {
                $query->where('ditugaskan_ke', $user->pelanggan->id_pelanggan);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // ========== DEFAULT STATUS = 'OPEN' ==========
        $status = $request->get('status', 'OPEN');
        if ($status) {
            $query->where('status', $status);
        }
        // =============================================

        // Filter lainnya (search, bisnis_unit, prioritas, kategori, tanggal)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor_tiket', 'LIKE', "%{$search}%")
                ->orWhere('judul', 'LIKE', "%{$search}%")
                ->orWhere('deskripsi', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('bisnis_unit_id')) {
            $query->where('bisnis_unit_id', $request->bisnis_unit_id);
        }

        if ($request->filled('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        if ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $query->where('created_at', '>=', $startDate);
        }

        if ($request->filled('end_date')) {
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->where('created_at', '<=', $endDate);
        }

        $query->orderBy('created_at', 'desc');
        $tiket = $query->paginate(15)->withQueryString();

        return view('help_p.proses.index', compact('tiket', 'bisnisUnits', 'kategori'));
    }

    /**
     * Query tiket sesuai hak akses unit bisnis user (sama seperti index()).
     */
    protected function aksesQuery($user)
    {
        $query = HelpTiket::query();

        $accessibleUnits = $user->getAccessibleBusinessUnits();
        if (!empty($accessibleUnits)) {
            $query->whereIn('bisnis_unit_id', $accessibleUnits);
        } elseif ($user->pelanggan) {
            $query->where('ditugaskan_ke', $user->pelanggan->id_pelanggan);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Filter bersama halaman Timeline & Grafik: bisnis unit, cari, tahun, bulan, status.
     * $penuh = false (dipakai endpoint AJAX) melewatkan query daftar tahun.
     */
    protected function siapkanFilter(Request $request, bool $penuh = true): array
    {
        $user     = Auth::user();
        $base     = $this->aksesQuery($user);
        $sekarang = now();

        $daftarTahun = [];
        if ($penuh) {
            $minTahun    = optional((clone $base)->min('created_at'), fn ($d) => Carbon::parse($d)->year) ?? $sekarang->year;
            $tahunPilih  = (int) $request->get('tahun', $sekarang->year);
            $daftarTahun = range($sekarang->year, min($minTahun, $tahunPilih, $sekarang->year));
        }

        // Business Unit yang boleh dipilih
        $unitIds     = $user->getAccessibleBusinessUnits();
        $bisnisUnits = BisnisUnit::orderBy('nama_bisnis_unit')
            ->when(!empty($unitIds), fn ($q) => $q->whereIn('id_bisnis_unit', $unitIds))
            ->get();
        $terkunci     = $bisnisUnits->count() <= 1;
        $bisnisUnitId = ($request->filled('bisnis_unit_id') && !$terkunci) ? (int) $request->bisnis_unit_id : null;
        $search       = trim((string) $request->get('search', ''));

        if ($bisnisUnitId) {
            $base->where('bisnis_unit_id', $bisnisUnitId);
        }
        if ($search !== '') {
            $base->where(function ($w) use ($search) {
                $w->where('nomor_tiket', 'LIKE', "%{$search}%")->orWhere('judul', 'LIKE', "%{$search}%");
            });
        }

        // Periode: default bulan & tahun berjalan; bulan kosong = seluruh tahun
        $tahun = max(2000, min($sekarang->year + 1, (int) $request->get('tahun', $sekarang->year)));
        $bulan = $request->has('tahun')
            ? ($request->filled('bulan') ? (int) $request->bulan : null)
            : $sekarang->month;
        if ($bulan !== null && ($bulan < 1 || $bulan > 12)) {
            $bulan = null;
        }

        $status  = in_array($request->get('status'), ['aktif', 'closed'], true) ? $request->status : 'semua';
        $rentang = $bulan
            ? \App\Support\help_s\HelpTimeline::rentangBulan($tahun, $bulan)
            : \App\Support\help_s\HelpTimeline::rentangTahun($tahun);

        // Tiket yang beririsan dengan periode (belum close, atau ditutup di/sesudah awal periode)
        $qPeriode = (clone $base)
            ->where('created_at', '<=', $rentang['akhir'])
            ->where(function ($w) use ($rentang) {
                $w->where('status', '!=', 'CLOSED')
                  ->orWhereRaw('COALESCE(ditutup_pada, diselesaikan_pada, updated_at) >= ?', [$rentang['awal']]);
            });

        return [
            'base' => $base, 'qPeriode' => $qPeriode, 'rentang' => $rentang, 'sekarang' => $sekarang,
            'tahun' => $tahun, 'bulan' => $bulan, 'status' => $status, 'daftarTahun' => $daftarTahun,
            'bisnisUnits' => $bisnisUnits, 'bisnisUnitId' => $bisnisUnitId,
            'bisnisUnitTerkunci' => $terkunci, 'search' => $search,
        ];
    }

    /**
     * Halaman TIMELINE (Gantt kalender). Menu sidebar: Timeline.
     *
     * Dibuat ringan: maksimal $batas baris, hanya relasi yang dipakai.
     */
    public function timeline(Request $request)
    {
        $f = $this->siapkanFilter($request);
        extract($f, EXTR_SKIP);   // $base $qPeriode $rentang $sekarang $tahun $bulan $status ...

        $q = (clone $qPeriode)->with(['ditugaskanKe', 'logStatus', 'vendorProses', 'bisnisUnit']);
        if ($status === 'aktif') {
            $q->where('status', '!=', 'CLOSED');
        } elseif ($status === 'closed') {
            $q->where('status', 'CLOSED');
        }

        $batas      = 100;
        $totalTiket = (clone $q)->count();
        $semua      = $q->orderBy('created_at')->limit($batas)->get();

        $aktif  = $semua->where('status', '!=', 'CLOSED')->sortBy('created_at');
        $closed = $semua->where('status', 'CLOSED')
            ->sortByDesc(fn ($t) => \App\Support\help_s\HelpTimeline::waktuTutup($t)->timestamp);

        $ganttAktif  = $aktif->reject(fn ($t) => $t->di_vendor)->values();
        $ganttClosed = $closed->reject(fn ($t) => $t->di_vendor)->values();
        $ganttVendor = $aktif->concat($closed)->filter(fn ($t) => $t->di_vendor)->values();

        // ---------- ringkasan ----------
        $tujuhHari = 7 * 86400;
        $umur      = fn ($t) => $sekarang->timestamp - Carbon::parse($t->created_at)->timestamp;

        $closedPeriode = $closed->filter(fn ($t) => \App\Support\help_s\HelpTimeline::waktuTutup($t)
            ->between($rentang['awal'], $rentang['akhir']));
        // lama closed dalam DETIK (1 hari = 24 jam penuh; tampilan hari + jam dibuat di view)
        $lamaClosed = $closedPeriode->map(fn ($t) => max(0,
            \App\Support\help_s\HelpTimeline::waktuTutup($t)->timestamp - Carbon::parse($t->created_at)->timestamp
        ));

        // lama sampai DONE (pengerjaan selesai, bukan ditutup) untuk tiket yang selesai di periode ini, dalam DETIK
        $lamaDone = $semua->map(function ($t) use ($rentang) {
            $s = \App\Support\help_s\HelpTimeline::waktuSelesai($t);

            return ($s && $s->between($rentang['awal'], $rentang['akhir']))
                ? max(0, $s->timestamp - Carbon::parse($t->created_at)->timestamp)
                : null;
        })->filter(fn ($d) => $d !== null);

        $menunggu = $semua
            ->filter(fn ($t) => Carbon::parse($t->created_at)->between($rentang['awal'], $rentang['akhir']))
            ->map(function ($t) {
                $d = \App\Support\help_s\HelpTimeline::waktuDiambil($t);
                return $d ? max(0, $d->timestamp - Carbon::parse($t->created_at)->timestamp) : null;
            })
            ->filter(fn ($d) => $d !== null);

        $ringkasan = [
            'menunggu_detik'   => $menunggu->isNotEmpty() ? (int) round($menunggu->avg()) : null,
            'belum_diambil'    => $aktif->filter(fn ($t) => $t->status === 'OPEN')->count(),
            'aktif'            => $aktif->count(),
            'telat'            => $aktif->filter(fn ($t) => $umur($t) > $tujuhHari)->count(),
            'closed'           => $closedPeriode->count(),
            'rata_detik'       => $lamaDone->isNotEmpty() ? (int) round($lamaDone->avg()) : null,
            'persen_ok'        => $lamaClosed->isNotEmpty()
                ? (int) round($lamaClosed->filter(fn ($d) => $d <= $tujuhHari)->count() / $lamaClosed->count() * 100) : null,
        ];

        return view('help_p.timeline.index', compact(
            'rentang', 'tahun', 'bulan', 'status', 'daftarTahun',
            'bisnisUnits', 'bisnisUnitId', 'bisnisUnitTerkunci', 'search',
            'ganttAktif', 'ganttClosed', 'ganttVendor', 'ringkasan',
            'totalTiket', 'batas'
        ));
    }

    /**
     * Halaman GRAFIK: batang jumlah tiket per Business Unit (admin).
     * Hanya hitungan (1 query GROUP BY); daftar tiket dimuat saat batang diklik (grafikDaftar).
     */
    public function grafik(Request $request)
    {
        $f = $this->siapkanFilter($request);
        extract($f, EXTR_SKIP);

        $hitung = (clone $qPeriode)
            ->selectRaw("bisnis_unit_id,
                         SUM(CASE WHEN status <> 'CLOSED' THEN 1 ELSE 0 END) AS aktif,
                         SUM(CASE WHEN status =  'CLOSED' THEN 1 ELSE 0 END) AS closed")
            ->groupBy('bisnis_unit_id')
            ->get()
            ->keyBy('bisnis_unit_id');

        $grafikBU = [];
        foreach ($bisnisUnits as $bu) {
            if ($bisnisUnitId && (int) $bu->id_bisnis_unit !== $bisnisUnitId) {
                continue;
            }
            $info = \App\Support\help_s\HelpTimeline::infoBisnisUnit($bu->nama_bisnis_unit);
            $row  = $hitung[$bu->id_bisnis_unit] ?? null;

            $grafikBU[] = [
                'id'     => $bu->id_bisnis_unit,
                'label'  => $bu->nama_bisnis_unit,
                'warna'  => $info['warna'],
                'urut'   => $info['urut'],
                'aktif'  => (int) ($row->aktif ?? 0),
                'closed' => (int) ($row->closed ?? 0),
            ];
        }
        usort($grafikBU, fn ($a, $b) => [$a['urut'], $a['label']] <=> [$b['urut'], $b['label']]);

        return view('help_p.grafik.index', compact(
            'rentang', 'tahun', 'bulan', 'daftarTahun',
            'bisnisUnits', 'bisnisUnitId', 'bisnisUnitTerkunci', 'search', 'grafikBU'
        ));
    }

    /**
     * JSON daftar tiket satu Business Unit (dipanggil saat batang diklik).
     * Maksimal 100 tiket: yang belum close lebih dulu, paling lama di atas.
     */
    public function grafikDaftar(Request $request)
    {
        $f    = $this->siapkanFilter($request, false);
        $mode = in_array($request->get('mode'), ['aktif', 'closed', 'semua'], true) ? $request->mode : 'aktif';

        $q = (clone $f['qPeriode'])
            ->where('bisnis_unit_id', (int) $request->get('bu'))
            ->when($mode === 'aktif',  fn ($x) => $x->where('status', '!=', 'CLOSED'))
            ->when($mode === 'closed', fn ($x) => $x->where('status', 'CLOSED'));

        $total = (clone $q)->count();
        $rows  = $q->with('ditugaskanKe')
            ->orderByRaw("CASE WHEN status = 'CLOSED' THEN 1 ELSE 0 END")
            ->orderBy('created_at')
            ->limit(100)
            ->get();

        $sekarang = $f['sekarang'];
        $data = $rows->map(function ($t) use ($sekarang) {
            $tutup = \App\Support\help_s\HelpTimeline::waktuTutup($t);
            $mulai = Carbon::parse($t->created_at);
            $detik = max(0, ($tutup ?? $sekarang)->timestamp - $mulai->timestamp);

            return [
                'nomor'  => $t->nomor_tiket,
                'judul'  => $t->judul,
                'pic'    => optional($t->ditugaskanKe)->nama_pelanggan ?: 'Belum ditugaskan',
                'status' => str_replace('_', ' ', $t->status),
                'closed' => $tutup !== null,
                'umur'   => \App\Support\help_s\HelpTimeline::formatDurasi($detik),
                'telat'  => $detik > 7 * 86400,
                'sejak'  => $mulai->format('d M Y'),
                'url'    => route('help.proses.show', $t),
            ];
        })->values();

        return response()->json(['total' => $total, 'data' => $data]);
    }

    /**
     * Display the specified ticket.
     */
    public function show($id)
    {
        $user = Auth::user();

        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }

        $tiket = HelpTiket::with([
            'kategori',
            'bisnisUnit',
            'pelapor.user',
            'ditugaskanKe.user',
            'vendorProses',
            'komentar' => function($query) {
                $query->with(['pengguna.user'])
                      ->orderBy('created_at', 'asc');
            },
            'lampiran' => function($query) {
                $query->with(['pengguna.user'])
                      ->orderBy('created_at', 'asc');
            },
            'logStatus' => function($query) {
                $query->with(['pengguna.user'])
                      ->orderBy('created_at', 'asc');
            }
        ])->findOrFail($id);

        if (!$user->canAccessTicket($tiket)) {
            abort(403, 'Anda tidak memiliki akses untuk melihat tiket ini.');
        }

        $isAssigned = $tiket->ditugaskan_ke == $user->pelanggan->id_pelanggan;
        $lampiran = $this->klasifikasiLampiran($tiket->lampiran, $tiket->pelapor_id);

        // ============================================================
        // DAFTAR PETUGAS GA UNTUK REASSIGN (dengan perbaikan collation)
        // ============================================================
        $assignableUsers = Pelanggan::whereIn('id_pelanggan', function($query) {
            $query->select('p.id_pelanggan')
                  ->from('tb_pelanggan as p')
                  ->join('users as u', 'p.id_login', '=', 'u.id')
                  ->join('tb_access_menu as am', function($join) {
                      $join->on('u.username', '=', DB::raw('am.username COLLATE utf8mb4_unicode_ci'));
                  })
                  ->where(function($q) {
                      $q->where('am.ga_help_proses', 1)
                        ->orWhere('am.ga_help_full_akses', 1);
                  });
        })->with('user')->get();

        return view('help_p.proses.show', compact('tiket', 'isAssigned', 'lampiran', 'assignableUsers'));
    }

    /**
     * Take/assign ticket to current user.
     */
    public function take(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->pelanggan) {
            return redirect()->back()->with('error', 'Data pelanggan belum terhubung.');
        }

        $tiket = HelpTiket::findOrFail($id);

        if (!$user->canAccessTicket($tiket)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk mengambil tiket ini.');
        }

        if (!in_array($tiket->status, [self::STATUS_TIKET['OPEN'], self::STATUS_TIKET['HELP_GA_CORP']])) {
            return redirect()->back()->with('error', 'Hanya tiket dengan status OPEN atau HELP GA CORP yang dapat diambil.');
        }

        try {
            DB::beginTransaction();

            $oldStatus = $tiket->status;

            $tiket->update([
                'status' => self::STATUS_TIKET['ON_PROCESS'],
                'ditugaskan_ke' => $user->pelanggan->id_pelanggan,
                'diproses_pada' => now()
            ]);

            HelpLogStatus::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'status_lama' => $oldStatus,
                'status_baru' => self::STATUS_TIKET['ON_PROCESS'],
                'catatan' => 'Tiket diambil oleh ' . ($user->name ?? $user->username)
            ]);

            HelpKomentar::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'komentar' => 'Tiket sedang diproses',
                'pesan_sistem' => true,
                'tipe_pesan_sistem' => 'STATUS_CHANGED'
            ]);

            DB::commit();

            return redirect()->route('help.proses.show', $tiket->id)
                ->with('success', 'Tiket berhasil diambil dan sedang diproses!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal mengambil tiket', [
                'tiket_id' => $tiket->id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Gagal mengambil tiket: ' . $e->getMessage());
        }
    }

    /**
     * Add comment to ticket.
     */
    public function addKomentar(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }

        $tiket = HelpTiket::findOrFail($id);

        if ($tiket->status === self::STATUS_TIKET['CLOSED']) {
            return back()->with('error', 'Tiket yang sudah ditutup tidak dapat dikomentari.');
        }

        $validated = $request->validate([
            'komentar' => 'required|string',
            'lampiran.*' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx'
        ]);

        try {
            DB::beginTransaction();

            $komentar = HelpKomentar::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'komentar' => $validated['komentar']
            ]);

            if ($request->hasFile('lampiran')) {
                foreach ($request->file('lampiran') as $file) {
                    $this->simpanLampiran(
                        $tiket,
                        $file,
                        self::LAMPIRAN_TIPE['FOLLOW_UP'],
                        $user->pelanggan->id_pelanggan
                    );
                }
            }

            DB::commit();

            return back()->with('success', 'Komentar berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menambahkan komentar', [
                'tiket_id' => $tiket->id,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Gagal menambahkan komentar: ' . $e->getMessage());
        }
    }

    /**
     * Download report as CSV.
     */
    public function download(Request $request)
    {
        $user = Auth::user();

        if (!$user->pelanggan) {
            return redirect()->route('help.proses.index')
                ->with('error', 'Data pelanggan belum terhubung.');
        }

        $query = HelpTiket::with([
            'pelapor.user',
            'kategori',
            'bisnisUnit',
            'ditugaskanKe.user'
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor_tiket', 'LIKE', "%{$search}%")
                  ->orWhere('judul', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('bisnis_unit_id')) {
            $query->where('bisnis_unit_id', $request->bisnis_unit_id);
        }

        if ($request->filled('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        if ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $query->where('created_at', '>=', $startDate);
        }

        if ($request->filled('end_date')) {
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->where('created_at', '<=', $endDate);
        }

        if ($request->has('ignore_filters') && $request->ignore_filters == '1') {
            $query = HelpTiket::with([
                'pelapor.user',
                'kategori',
                'bisnisUnit',
                'ditugaskanKe.user'
            ]);
        }

        $query->orderBy('created_at', 'desc');
        $tiket = $query->get();

        if ($tiket->isEmpty()) {
            return redirect()->route('help.proses.index')
                ->with('error', 'Tidak ada data tiket untuk didownload');
        }

        $filename = 'tiket_proses_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($tiket) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'No',
                'Nomor Tiket',
                'Judul',
                'Pelapor',
                'Bisnis Unit',
                'Kategori',
                'Prioritas',
                'Status',
                'Penanggung Jawab',
                'Tanggal Dibuat',
                'Tanggal Diproses',
                'Tanggal Selesai',
                'Deskripsi'
            ]);

            foreach ($tiket as $index => $item) {
                $pelaporName = optional(optional($item->pelapor)->user)->name
                    ?? optional($item->pelapor)->nama
                    ?? '-';

                $penanggungJawab = optional(optional($item->ditugaskanKe)->user)->name
                    ?? optional($item->ditugaskanKe)->nama
                    ?? 'Belum ditugaskan';

                $bisnisUnit = optional($item->bisnisUnit)->nama_bisnis_unit ?? '-';
                $kategori = optional($item->kategori)->nama ?? '-';

                fputcsv($file, [
                    $index + 1,
                    $item->nomor_tiket,
                    $item->judul,
                    $pelaporName,
                    $bisnisUnit,
                    $kategori,
                    $item->prioritas,
                    $item->status,
                    $penanggungJawab,
                    $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-',
                    $item->diproses_pada ? Carbon::parse($item->diproses_pada)->format('d/m/Y H:i') : '-',
                    $item->diselesaikan_pada ? Carbon::parse($item->diselesaikan_pada)->format('d/m/Y H:i') : '-',
                    $item->deskripsi
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Preview attachment.
     */
    public function previewLampiran(HelpLampiran $lampiran)
    {
        Log::info('Preview lampiran', ['lampiran_id' => $lampiran->id]);

        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        if (!str_contains($lampiran->tipe_file, 'image')) {
            return $this->downloadLampiran($lampiran);
        }

        $path = $this->cariPathFile($lampiran);

        if (!$path || !file_exists($path)) {
            abort(404, 'File tidak ditemukan');
        }

        return response()->file($path, [
            'Content-Type' => $lampiran->tipe_file,
            'Content-Disposition' => 'inline; filename="' . $lampiran->nama_file . '"',
        ]);
    }

    /**
     * Download attachment.
     */
    public function downloadLampiran(HelpLampiran $lampiran)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        $path = $this->cariPathFile($lampiran);

        if (!$path || !file_exists($path)) {
            abort(404, 'File tidak ditemukan');
        }

        Log::info('Download lampiran', [
            'user_id' => $user->id,
            'lampiran_id' => $lampiran->id
        ]);

        return response()->download($path, $lampiran->nama_file);
    }

    // ==================== HELPER METHODS ====================

    private function klasifikasiLampiran($lampiran, $pelaporId)
    {
        $result = [
            'initial' => ['photos' => collect(), 'documents' => collect()],
            'follow_up' => ['photos' => collect(), 'documents' => collect()],
            'completion' => ['photos' => collect(), 'documents' => collect()],
            'all_photos' => collect()
        ];

        foreach ($lampiran as $item) {
            $isImage = str_contains($item->tipe_file, 'image');
            $item->formatted_size = $this->formatBytes($item->ukuran_file);

            if ($item->tipe === self::LAMPIRAN_TIPE['INITIAL']) {
                if ($isImage) {
                    $item->badge_text = 'Awal';
                    $item->badge_color = 'bg-blue-100 text-blue-800';
                    $result['initial']['photos']->push($item);
                } else {
                    $result['initial']['documents']->push($item);
                }
            } elseif ($item->tipe === self::LAMPIRAN_TIPE['FOLLOW_UP']) {
                if ($isImage) {
                    $item->badge_text = $item->pengguna_id == $pelaporId ? 'Pelapor' : 'Petugas';
                    $item->badge_color = $item->pengguna_id == $pelaporId
                        ? 'bg-blue-100 text-blue-800'
                        : 'bg-gray-100 text-gray-800';
                    $result['follow_up']['photos']->push($item);
                } else {
                    $result['follow_up']['documents']->push($item);
                }
            } elseif ($item->tipe === self::LAMPIRAN_TIPE['COMPLETION']) {
                if ($isImage) {
                    $item->badge_text = 'Selesai';
                    $item->badge_color = 'bg-green-100 text-green-800';
                    $result['completion']['photos']->push($item);
                } else {
                    $result['completion']['documents']->push($item);
                }
            }

            if ($isImage) {
                $result['all_photos']->push($item);
            }
        }

        $result['all_photos'] = $result['all_photos']->sortBy('created_at')->values();
        return $result;
    }

    private function simpanLampiran($tiket, $file, $tipe, $penggunaId)
    {
        try {
            if (!$file || !$file->isValid()) {
                throw new \Exception('File tidak valid atau corrupt');
            }

            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();

            if (!in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'])) {
                throw new \Exception('Format file tidak diizinkan');
            }

            $safeName = pathinfo($originalName, PATHINFO_FILENAME);
            $safeName = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $safeName);
            $safeName = substr($safeName, 0, 50);

            $prefix = strtolower($tipe) . '_';
            $fileName = $prefix . time() . '_' . uniqid() . '_' . $safeName . '.' . $extension;

            $directory = 'help/tiket/' . $tiket->id;
            $path = $directory . '/' . $fileName;

            if (!Storage::disk('private')->exists($directory)) {
                Storage::disk('private')->makeDirectory($directory);
            }

            $fileContent = file_get_contents($file->getRealPath());
            if ($fileContent === false) {
                throw new \Exception('Gagal membaca file');
            }

            $result = Storage::disk('private')->put($path, $fileContent);
            if (!$result) {
                throw new \Exception('Gagal menyimpan file ke storage');
            }

            if (!Storage::disk('private')->exists($path)) {
                throw new \Exception('File tidak ditemukan setelah disimpan');
            }

            $lampiran = HelpLampiran::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $penggunaId,
                'path_file' => $path,
                'nama_file' => $originalName,
                'tipe_file' => $file->getMimeType(),
                'ukuran_file' => $file->getSize(),
                'tipe' => $tipe
            ]);

            return $lampiran;
        } catch (\Exception $e) {
            Log::error('Gagal simpan lampiran: ' . $e->getMessage(), [
                'tiket_id' => $tiket->id,
                'tipe' => $tipe,
                'file' => $file ? $file->getClientOriginalName() : 'NULL'
            ]);
            throw $e;
        }
    }

    private function cariPathFile($lampiran)
    {
        $possiblePaths = [
            storage_path('app/private/' . $lampiran->path_file),
            storage_path('app/' . $lampiran->path_file),
            storage_path('app/private/help/tiket/' . $lampiran->tiket_id . '/' . basename($lampiran->path_file)),
            storage_path('app/help/tiket/' . $lampiran->tiket_id . '/' . basename($lampiran->path_file)),
            storage_path('app/private/help/tiket/' . $lampiran->tiket_id . '/' . $lampiran->nama_file),
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        if (Storage::disk('private')->exists($lampiran->path_file)) {
            return Storage::disk('private')->path($lampiran->path_file);
        }

        if (Storage::disk('private')->exists('help/tiket/' . $lampiran->tiket_id . '/' . basename($lampiran->path_file))) {
            return Storage::disk('private')->path('help/tiket/' . $lampiran->tiket_id . '/' . basename($lampiran->path_file));
        }

        return null;
    }

    private function formatBytes($bytes, $precision = 1)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}