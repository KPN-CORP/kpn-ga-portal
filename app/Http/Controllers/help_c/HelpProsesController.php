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
        // Dropdown BU hanya berisi BU yang boleh diakses petugas ini
        $unitIds     = $user->getAccessibleBusinessUnits();
        $bisnisUnits = BisnisUnit::orderBy('nama_bisnis_unit')
            ->when(!empty($unitIds), fn ($q) => $q->whereIn('id_bisnis_unit', $unitIds))
            ->get();
        $kategori = HelpKategori::where('aktif', true)->orderBy('nama')->get();

        // Hak akses BU (+ tiket yang ditugaskan ke petugas ini) dipusatkan di aksesQuery()
        $query = $this->aksesQuery($user)->with([
            'kategori',
            'bisnisUnit',
            'pelapor.user',
            'ditugaskanKe.user',
            'logStatus',
            'vendorProses'
        ]);

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
        $query   = HelpTiket::query();
        $unitIds = $user->getAccessibleBusinessUnits();
        $pelId   = optional($user->pelanggan)->id_pelanggan;

        // BU yang boleh diakses ATAU tiket yang ditugaskan langsung ke petugas ini
        // (mis. tiket BU lain yang dialihkan ke dia) — supaya tidak hilang dari daftar, Timeline, dan Grafik.
        $query->where(function ($q) use ($unitIds, $pelId) {
            if (!empty($unitIds)) {
                $q->whereIn('bisnis_unit_id', $unitIds);
            }
            if ($pelId) {
                $q->orWhere('ditugaskan_ke', $pelId);
            }
            if (empty($unitIds) && !$pelId) {
                $q->whereRaw('1 = 0');
            }
        });

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

        // Periode: rentang tanggal bebas (?dari=YYYY-MM-DD&sampai=YYYY-MM-DD).
        // Tanpa parameter: bulan berjalan. Parameter lama tahun/bulan tetap didukung (bulan kosong = seluruh tahun).
        $HT       = \App\Support\help_s\HelpTimeline::class;
        $parseTgl = function ($v) {
            try {
                return $v ? Carbon::createFromFormat('Y-m-d', (string) $v)->startOfDay() : null;
            } catch (\Throwable $e) {
                return null;
            }
        };
        $dari   = $parseTgl($request->get('dari'));
        $sampai = $parseTgl($request->get('sampai'));

        if ($dari || $sampai) {
            $dari   = $dari ?: $sampai->copy();
            $sampai = $sampai ?: $dari->copy();
            if ($sampai->lt($dari)) {
                [$dari, $sampai] = [$sampai, $dari];
            }
            $dari   = $dari->max(Carbon::create(2000, 1, 1));
            $sampai = $sampai->min($dari->copy()->addYears(5));   // batas wajar agar grafik tetap ringan

            $rentang = $HT::rentang($dari, $sampai);
        } else {
            $tahun = max(2000, min($sekarang->year + 1, (int) $request->get('tahun', $sekarang->year)));
            $bulan = $request->has('tahun')
                ? ($request->filled('bulan') ? (int) $request->bulan : null)
                : $sekarang->month;
            if ($bulan !== null && ($bulan < 1 || $bulan > 12)) {
                $bulan = null;
            }
            $rentang = $bulan ? $HT::rentangBulan($tahun, $bulan) : $HT::rentangTahun($tahun);
        }

        // tahun / bulan tetap diisi (dipakai view lain): bulan hanya bila rentang = 1 bulan penuh
        $tahun = $rentang['awal']->year;
        $bulan = ($rentang['awal']->day === 1 && $rentang['awal']->isSameMonth($rentang['akhir'])
                  && $rentang['akhir']->isSameDay($rentang['awal']->copy()->endOfMonth()))
            ? $rentang['awal']->month : null;

        $status = in_array($request->get('status'), ['aktif', 'closed'], true) ? $request->status : 'semua';

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

        $q = (clone $qPeriode)->with(['ditugaskanKe', 'logStatus.pengguna', 'vendorProses', 'bisnisUnit']);
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
            // Open = HELP_GA_CORP + OPEN · On proses = ON_PROCESS + WAITING (termasuk yang di vendor)
            'open'             => $aktif->whereIn('status', ['HELP_GA_CORP', 'OPEN'])->count(),
            'on_proses'        => $aktif->whereIn('status', ['ON_PROCESS', 'WAITING'])->count(),
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
     * Halaman GRAFIK (dashboard): ringkasan waktu, ketergantungan tiap BU pada GA Corp,
     * ketepatan SLA per kategori, dan beban kerja per admin. Maksimal 1000 tiket per tampilan.
     */
    public function grafik(Request $request)
    {
        $f = $this->siapkanFilter($request);
        extract($f, EXTR_SKIP);

        $HT = \App\Support\help_s\HelpTimeline::class;

        $tikets = (clone $qPeriode)
            ->with(['logStatus.pengguna', 'ditugaskanKe', 'bisnisUnit', 'kategori'])
            ->orderByDesc('created_at')
            ->limit(1000)
            ->get();
        $totalTiket = (clone $qPeriode)->count();
        $terpotong  = $totalTiket > $tikets->count();
        $perBU      = $tikets->groupBy('bisnis_unit_id');

        // ---- per Business Unit: berapa persen tiket yang dialihkan ke GA Corp, dan berapa yang selesai di unit sendiri
        $grafikBU = [];
        foreach ($bisnisUnits as $bu) {
            if ($bisnisUnitId && (int) $bu->id_bisnis_unit !== $bisnisUnitId) {
                continue;
            }
            $grup  = $perBU[$bu->id_bisnis_unit] ?? collect();
            $info  = $HT::infoBisnisUnit($bu->nama_bisnis_unit);
            $rekap = $HT::rekapAdmin($grup, $sekarang);
            $n     = $grup->count();

            // selesai di unit sendiri = sudah Done/Closed dan tidak pernah dialihkan ke GA Corp
            $selUnit = 0;
            foreach ($grup as $tk) {
                if ($HT::jejakAdmin($tk, $sekarang)['over'] === null && $HT::waktuSelesai($tk) !== null) {
                    $selUnit++;
                }
            }

            $grafikBU[] = [
                'id'       => $bu->id_bisnis_unit,
                'label'    => $bu->nama_bisnis_unit,
                'warna'    => $info['warna'],
                'urut'     => $info['urut'],
                'tiket'    => $n,
                'eskalasi' => $rekap['eskalasi'],
                'persen'   => $n > 0 ? (int) round($rekap['eskalasi'] / $n * 100) : 0,
                'selesai_unit' => $selUnit,
                'persen_unit'  => $n > 0 ? (int) round($selUnit / $n * 100) : 0,
            ];
        }
        usort($grafikBU, fn ($a, $b) => [$a['urut'], $a['label']] <=> [$b['urut'], $b['label']]);
        $grafikBU   = array_values(array_filter($grafikBU, fn ($g) => $g['tiket'] > 0));
        $eskalasiBU = collect($grafikBU)->sortByDesc('persen')->values()->all();
        $selesaiUnitBU = collect($grafikBU)->sortByDesc('persen_unit')->values()->all();

        // ---- ringkasan (rata-rata menunggu, pengerjaan, eskalasi)
        $semua = $HT::rekapAdmin($tikets, $sekarang);

        // ---- ketepatan SLA per kategori: dibuat -> Done dibanding sla_jam kategori
        $sla = [];
        foreach ($tikets as $t) {
            $kat = $t->kategori;
            if (!$kat || !$kat->sla_jam) {
                continue;
            }
            $selesai = $HT::waktuSelesai($t);
            if (!$selesai || !$selesai->between($rentang['awal'], $rentang['akhir'])) {
                continue;
            }
            $detik = max(0, $selesai->timestamp - Carbon::parse($t->created_at)->timestamp);
            $sla[$kat->id] ??= ['nama' => $kat->nama, 'sla_jam' => (int) $kat->sla_jam, 'total' => 0, 'dalam' => 0, 'detik' => []];
            $sla[$kat->id]['total']++;
            $sla[$kat->id]['detik'][] = $detik;
            if ($detik <= $kat->sla_jam * 3600) {
                $sla[$kat->id]['dalam']++;
            }
        }
        $slaKategori = collect($sla)->map(fn ($r, $id) => [
            'id'      => $id,
            'nama'    => $r['nama'],
            'sla_jam' => $r['sla_jam'],
            'total'   => $r['total'],
            'dalam'   => $r['dalam'],
            'persen'  => (int) round($r['dalam'] / $r['total'] * 100),
            'rata'    => (int) round(array_sum($r['detik']) / count($r['detik'])),
        ])->sortByDesc('total')->values()->all();
        $slaTotal = array_sum(array_column($slaKategori, 'total'));
        $slaDalam = array_sum(array_column($slaKategori, 'dalam'));

        // ---- umur tiket yang belum close (per sekarang, semua periode; mengikuti filter BU/pencarian)
        $batasHari = (int) $HT::BATAS_HARI;
        $umurKey   = function (int $detik) use ($batasHari) {
            $hari = $detik / 86400;

            return $hari < 1 ? 'u0' : ($hari < 3 ? 'u1' : ($hari <= $batasHari ? 'u2' : 'u3'));
        };
        $backlog = (clone $base)->where('status', '!=', 'CLOSED')
            ->with(['logStatus.pengguna', 'ditugaskanKe', 'bisnisUnit', 'kategori'])
            ->orderBy('created_at')
            ->limit(1000)
            ->get();
        $hitungUmur = ['u0' => 0, 'u1' => 0, 'u2' => 0, 'u3' => 0];
        $umurTertua = null;
        foreach ($backlog as $t) {
            $detikUmur = max(0, $sekarang->timestamp - Carbon::parse($t->created_at)->timestamp);
            $hitungUmur[$umurKey($detikUmur)]++;
            if ($umurTertua === null || $detikUmur > $umurTertua['detik']) {
                $umurTertua = ['detik' => $detikUmur, 'nomor' => $t->nomor_tiket];
            }
        }
        // ---- lama tiket sampai close: tiket yang ditutup (Closed) pada periode terpilih, dihitung sejak diajukan
        $hitungClose = ['c0' => 0, 'c1' => 0, 'c2' => 0, 'c3' => 0];
        $closeTerlama = null;
        foreach ($tikets as $t) {
            $tutup = $HT::waktuTutup($t);
            if (!$tutup || !$tutup->between($rentang['awal'], $rentang['akhir'])) {
                continue;
            }
            $lama = max(0, $tutup->timestamp - Carbon::parse($t->created_at)->timestamp);
            $hitungClose['c' . substr($umurKey($lama), 1)]++;
            if ($closeTerlama === null || $lama > $closeTerlama['detik']) {
                $closeTerlama = ['detik' => $lama, 'nomor' => $t->nomor_tiket];
            }
        }
        $umur = [
            ['k' => 'u0', 'label' => 'Kurang dari 1 hari', 'n' => $hitungUmur['u0'], 'warna' => '#059669'],
            ['k' => 'u1', 'label' => '1 – 3 hari',         'n' => $hitungUmur['u1'], 'warna' => '#d97706'],
            ['k' => 'u2', 'label' => "3 – {$batasHari} hari", 'n' => $hitungUmur['u2'], 'warna' => '#ea580c'],
            ['k' => 'u3', 'label' => "Lebih dari {$batasHari} hari", 'n' => $hitungUmur['u3'], 'warna' => '#dc2626'],
        ];
        $umurClose = [
            ['k' => 'c0', 'label' => 'Kurang dari 1 hari', 'n' => $hitungClose['c0'], 'warna' => '#059669'],
            ['k' => 'c1', 'label' => '1 – 3 hari',         'n' => $hitungClose['c1'], 'warna' => '#d97706'],
            ['k' => 'c2', 'label' => "3 – {$batasHari} hari", 'n' => $hitungClose['c2'], 'warna' => '#ea580c'],
            ['k' => 'c3', 'label' => "Lebih dari {$batasHari} hari", 'n' => $hitungClose['c3'], 'warna' => '#dc2626'],
        ];
        $totalClose = array_sum($hitungClose);
        if ($totalClose === 0) {
            $insClose = 'Belum ada tiket yang ditutup pada periode ini.';
        } elseif ($hitungClose['c3'] > 0) {
            $insClose = "{$hitungClose['c3']} dari {$totalClose} tiket butuh lebih dari {$batasHari} hari sampai ditutup. Terlama: {$closeTerlama['nomor']} (" . $HT::formatDurasi($closeTerlama['detik']) . ').';
        } else {
            $insClose = "Semua {$totalClose} tiket ditutup dalam {$batasHari} hari atau kurang. Terlama: {$closeTerlama['nomor']} (" . $HT::formatDurasi($closeTerlama['detik']) . ').';
        }
        $totalBacklog = array_sum($hitungUmur);
        if ($totalBacklog === 0) {
            $insUmur = 'Tidak ada tiket yang menggantung. Semua tiket sudah ditutup.';
        } elseif ($hitungUmur['u3'] > 0) {
            $insUmur = "{$hitungUmur['u3']} dari {$totalBacklog} tiket sudah lebih dari {$batasHari} hari belum ditutup. Tertua: {$umurTertua['nomor']} (" . $HT::formatDurasi($umurTertua['detik']) . ').';
        } else {
            $insUmur = "Semua {$totalBacklog} tiket yang belum ditutup umurnya masih {$batasHari} hari atau kurang.";
        }

        // ---- data rincian tiket (dipakai panel kanan saat batang grafik diklik; semua angka bisa ditelusuri ke tiketnya)
        $labelHasil = ['DONE' => 'Done', 'OVER' => 'Ke GA Corp', 'ALIH' => 'Dialihkan', 'BERJALAN' => 'Berjalan', 'CLOSED' => 'Closed'];
        $fmtTs = fn ($ts) => $ts ? \App\Support\help_s\HelpTimeline::wib($ts, 'd M H:i') : null;
        $bangunRincian = function ($t) use ($HT, $sekarang, $rentang, $labelHasil, $fmtTs, $umurKey) {
            $j       = $HT::jejakAdmin($t, $sekarang);
            $selesaiO = $HT::waktuSelesai($t);
            $selesai = $selesaiO ? $selesaiO->timestamp : null;
            $kat     = $t->kategori;
            $slaJam  = $kat && $kat->sla_jam ? (int) $kat->sla_jam : null;
            $total   = $selesai ? max(0, $selesai - $j['laporan']) : null;
            $dalam   = ($slaJam && $selesai && $selesaiO->between($rentang['awal'], $rentang['akhir']))
                ? $total <= $slaJam * 3600 : null;
            $pertama = $j['segmen'][0] ?? null;

            return [
                'id'      => $t->id,
                'kt'      => (int) $t->kategori_id,   // id kategori (grafik SLA per kategori)
                'uc'      => ($tutupO = $HT::waktuTutup($t)) && $tutupO->between($rentang['awal'], $rentang['akhir'])
                    ? 'c' . substr($umurKey(max(0, $tutupO->timestamp - Carbon::parse($t->created_at)->timestamp)), 1) : null,
                'tutup'   => $fmtTs($tutupO ? $tutupO->timestamp : null),
                'unit'    => $j['over'] === null && $selesai !== null,
                'um'      => $t->status !== 'CLOSED' ? $umurKey(max(0, $sekarang->timestamp - Carbon::parse($t->created_at)->timestamp)) : null,
                'n'       => $t->nomor_tiket,
                'j'       => $t->judul,
                'u'       => route('help.proses.show', $t),
                'st'      => $t->status,
                'bu'      => (int) $t->bisnis_unit_id,
                'bn'      => optional($t->bisnisUnit)->nama_bisnis_unit ?: '-',
                'sla'     => $slaJam,
                'dibuat'  => $fmtTs($j['laporan']),
                'diambil' => $fmtTs($j['diambil']),
                'tunggu'  => $pertama && $pertama['tunggu'] !== null ? $HT::formatDurasi($pertama['tunggu']) : null,
                'esk'     => $j['over'] !== null,
                'over'    => $fmtTs($j['over']),
                'done'    => $fmtTs($selesai),
                'proses'  => $j['diambil'] && $selesai ? $HT::formatDurasi(max(0, $selesai - $j['diambil'])) : null,
                'total'   => $total !== null ? $HT::formatDurasi($total) : null,
                'dalam'   => $dalam,
                'admin'   => array_values(array_unique(array_column($j['segmen'], 'admin'))),
                'seg'     => array_map(fn ($s) => [
                    'a' => $s['admin'],
                    'm' => $fmtTs($s['mulai']),
                    'e' => $fmtTs($s['akhir']),
                    'h' => $labelHasil[$s['hasil']] ?? $s['hasil'],
                    'k' => $s['hasil'],
                    'd' => $HT::formatDurasi($s['akhir'] - $s['mulai']),
                ], $j['segmen']),
            ];
        };
        // 'pr' = tiket termasuk perhitungan periode ini (dipakai tabel beban kerja admin agar angka dan daftar tiketnya sama)
        $rincian  = $tikets->map(fn ($t) => $bangunRincian($t) + ['pr' => true])->values()->all();
        $sudahAda = array_flip(array_column($rincian, 'id'));
        foreach ($backlog as $t) {
            if (!isset($sudahAda[$t->id])) {
                $rincian[] = $bangunRincian($t) + ['pr' => false];   // tiket belum close di luar periode tetap bisa diklik dari grafik umur
            }
        }

        // ---- kesimpulan satu kalimat untuk tiap grafik
        $e1 = $eskalasiBU[0] ?? null;
        $insEsk = $e1 && $e1['eskalasi'] > 0
            ? "{$e1['label']} paling sering meminta bantuan GA Corp: {$e1['persen']}% tiketnya dialihkan ({$e1['eskalasi']} dari {$e1['tiket']} tiket)."
            : 'Belum ada tiket yang dialihkan ke GA Corp pada periode ini.';

        $u1 = $selesaiUnitBU[0] ?? null;
        $insUnit = $u1 && $u1['selesai_unit'] > 0
            ? "{$u1['label']} paling banyak menyelesaikan tiketnya sendiri: {$u1['persen_unit']}% ({$u1['selesai_unit']} dari {$u1['tiket']} tiket selesai tanpa bantuan GA Corp)."
            : 'Belum ada tiket yang selesai di unit sendiri pada periode ini.';

        $buruk = collect($slaKategori)->where('total', '>=', 3)->sortBy('persen')->first();
        $insSla = $buruk && $buruk['persen'] < 100
            ? "{$buruk['nama']} paling sering melewati SLA: hanya {$buruk['persen']}% tepat waktu ({$buruk['dalam']} dari {$buruk['total']} tiket)."
            : ($slaKategori ? 'Semua kategori selesai tepat waktu pada periode ini.' : null);

        // ---- KPI arus tiket pada periode: masuk (diajukan) dan selesai (Done/Closed)
        $awalTs      = $rentang['awal']->timestamp;
        $akhirTs     = $rentang['akhir']->timestamp;
        $trenMasuk   = 0;
        $trenSelesai = 0;
        foreach ($tikets as $t) {
            $dibuatTs = Carbon::parse($t->created_at)->timestamp;
            if ($dibuatTs >= $awalTs && $dibuatTs <= $akhirTs) {
                $trenMasuk++;
            }
            $sel = $HT::waktuSelesai($t);
            if ($sel && $sel->timestamp >= $awalTs && $sel->timestamp <= $akhirTs) {
                $trenSelesai++;
            }
        }

        // ---- KPI tambahan: kondisi tiket saat ini (tidak tergantung periode, mengikuti filter BU/pencarian)
        $belumClose = (clone $base)->where('status', '!=', 'CLOSED')->count();
        $lewatBatas = (clone $base)->where('status', '!=', 'CLOSED')
            ->where('created_at', '<', $sekarang->copy()->subDays($HT::BATAS_HARI))->count();
        $kpiTambah = [
            'masuk'      => $trenMasuk,
            'selesai'    => $trenSelesai,
            'belum_close' => $belumClose,
            'lewat'      => $lewatBatas,
            'batas'      => $HT::BATAS_HARI,
        ];

        // ---- beban kerja per admin (dari rekapAdmin): sudah urut dari tiket terbanyak
        $adminRekap = $semua['admin'];

        $ringkasan = [
            'tiket'      => $tikets->count(),
            'tunggu'     => $semua['tunggu'],
            'done'       => $semua['done'],
            'jml_done'   => $semua['jml_done'],
            'eskalasi'   => $semua['eskalasi'],
            'persen_esk' => $tikets->count() > 0 ? (int) round($semua['eskalasi'] / $tikets->count() * 100) : null,
            'sla'        => $slaTotal > 0 ? (int) round($slaDalam / $slaTotal * 100) : null,
            'sla_total'  => $slaTotal,
        ];

        return view('help_p.grafik.index', compact(
            'rentang', 'tahun', 'bulan', 'daftarTahun',
            'bisnisUnits', 'bisnisUnitId', 'bisnisUnitTerkunci', 'search',
            'grafikBU', 'eskalasiBU', 'selesaiUnitBU', 'insUnit', 'slaKategori', 'ringkasan', 'terpotong',
            'rincian', 'insEsk', 'kpiTambah', 'umur', 'insUmur', 'umurClose', 'insClose',
            'insSla', 'adminRekap'
        ));
    }

    /**
     * JSON rincian satu admin (dipanggil saat baris admin dibuka): daftar tiket yang dia tangani
     * beserta waktu dibuat, diambil, dan selesai / diteruskan.
     */
    public function grafikDaftar(Request $request)
    {
        $f  = $this->siapkanFilter($request, false);
        $HT = \App\Support\help_s\HelpTimeline::class;

        $tikets = (clone $f['qPeriode'])
            ->with(['logStatus.pengguna', 'ditugaskanKe', 'bisnisUnit'])
            ->orderBy('created_at')
            ->limit(1000)
            ->get();

        $rekap = $HT::rekapAdmin($tikets, $f['sekarang'], true);
        $a     = collect($rekap['admin'])->firstWhere('nama', trim((string) $request->get('admin')));

        if (!$a) {
            return response()->json(['daftar' => []]);
        }

        return response()->json([
            'nama'   => $a['nama'],
            'tiket'  => $a['tiket'],
            'daftar' => $a['daftar'],
        ]);
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
        })->with(['user.accessMenu', 'user.pelanggan'])->get()
          // hanya petugas yang berhak atas BU tiket ini
          ->filter(fn ($p) => $p->user && $p->user->canAccessTicket($tiket))
          ->values();

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

        if (!$user->isGaHelpAdmin()) {
            return redirect()->back()->with('error', 'Hanya petugas GA Help yang dapat mengambil tiket.');
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

        $query = $this->aksesQuery($user)->with([
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
            $query = $this->aksesQuery($user)->with([
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
                    \App\Support\help_s\HelpTimeline::wib($item->created_at),
                    \App\Support\help_s\HelpTimeline::wib($item->diproses_pada),
                    \App\Support\help_s\HelpTimeline::wib($item->diselesaikan_pada),
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