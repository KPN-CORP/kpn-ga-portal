<?php

namespace App\Support\help_s;

use App\Models\help_m\HelpTiket;
use Carbon\Carbon;

/**
 * Menghitung data grafik proses 7 hari untuk satu tiket Help.
 *
 * - Hitungan dimulai dari OPEN (created_at).
 * - Transfer ke HELP_GA_CORP tetap dihitung (tidak mereset hitungan).
 * - Berhenti saat CLOSED (ditutup_pada); selama belum CLOSED berjalan sampai sekarang.
 * - Warna hijau/merah hanya untuk tiket CLOSED:
 *   7 hari atau kurang = hijau, lebih dari 7 hari = merah.
 */
class HelpTimeline
{
    const BATAS_HARI    = 7;
    const JUMLAH_KOLOM  = 7;

    /**
     * Pecah durasi (detik) menjadi [hari penuh, sisa jam].
     * 1 hari = 24 jam penuh; sisanya dihitung dalam jam (dibulatkan ke bawah).
     */
    public static function pecahDurasi(int $detik): array
    {
        $detik = max(0, $detik);

        return [intdiv($detik, 86400), intdiv($detik % 86400, 3600)];
    }

    /**
     * Teks durasi: "2 hari 5 jam", "3 hari", "7 jam", atau "< 1 jam".
     */
    public static function formatDurasi(int $detik): string
    {
        [$hari, $jam] = self::pecahDurasi($detik);

        if ($hari === 0) {
            return $jam > 0 ? $jam . ' jam' : '< 1 jam';
        }

        return $hari . ' hari' . ($jam > 0 ? ' ' . $jam . ' jam' : '');
    }

    public static function build(HelpTiket $tiket): array
    {
        $mulai   = Carbon::parse($tiket->created_at);
        $selesai = self::waktuTutup($tiket) ?? now();

        $awal  = $mulai->timestamp;
        $akhir = max($awal, $selesai->timestamp);
        $detik = $akhir - $awal;

        $hari    = intdiv($detik, 86400);   // hari penuh (24 jam); sisa dihitung dalam jam
        $lewat   = $detik > self::BATAS_HARI * 86400;
        $ditutup = self::waktuTutup($tiket) !== null;
        $hasil   = $ditutup ? ($lewat ? 'bad' : 'ok') : null;

        $segmen = self::segmen($tiket, $awal, $akhir);

        $cells = [];
        for ($i = 0; $i < self::JUMLAH_KOLOM; $i++) {
            $s = $awal + ($i * 86400);
            $e = $s + 86400;

            $state = null;
            if ($s < $akhir) {
                $skor = [];
                foreach ($segmen as [$a, $b, $st]) {
                    $overlap = min($b, $e) - max($a, $s);
                    if ($overlap > 0) {
                        $skor[$st] = ($skor[$st] ?? 0) + $overlap;
                    }
                }
                if (!empty($skor)) {
                    arsort($skor);
                    $state = array_key_first($skor);
                }
            }

            $tanggal = Carbon::createFromTimestamp($s)->locale('id');

            $cells[] = [
                'label'  => $tanggal->isoFormat('ddd D'),
                'tgl'    => $tanggal->format('d/m/Y H:i'),
                'terisi' => $state !== null,
                'state'  => $state,
            ];
        }

        return [
            'hari'    => $hari,
            'durasi'  => self::formatDurasi($detik),
            'lewat'   => $lewat,
            'ditutup' => $ditutup,
            'hasil'   => $hasil,
            'extra'   => max(0, $hari - self::JUMLAH_KOLOM),
            'cells'   => $cells,
            'mulai'   => $mulai,
            'selesai' => $selesai,
        ];
    }


    // =====================================================================
    //  GRAFIK GANTT KALENDER
    //  Semua posisi dihitung berdasarkan waktu sebenarnya (bukan "7 hari dari sekarang"),
    //  sehingga tiket lama tetap terlihat di tanggal aslinya.
    // =====================================================================

    /**
     * Susun rentang kalender [awal, akhir] beserta kolom header.
     * Jenis kolom otomatis: hari (<= 62 hari), minggu (<= 200 hari), bulan (lebih panjang).
     * Tiap kolom membawa bobot 'hari' agar lebar kolom proporsional dengan waktu.
     */
    public static function rentang(Carbon $awal, Carbon $akhir): array
    {
        $awal  = $awal->copy()->startOfDay();
        $akhir = $akhir->copy()->endOfDay();
        $now   = now();

        $totalHari = (int) round(($akhir->copy()->startOfDay()->timestamp - $awal->timestamp) / 86400) + 1;
        $jenis     = $totalHari <= 62 ? 'hari' : ($totalHari <= 200 ? 'minggu' : 'bulan');

        $kolom = [];
        if ($jenis === 'hari') {
            for ($d = $awal->copy(); $d->lte($akhir); $d->addDay()) {
                $l = $d->copy()->locale('id');
                $kolom[] = [
                    'label' => $l->format('j'),
                    'sub'   => '',        // hanya angka tanggal, tanpa nama hari
                    'hari'  => 1,
                    'ini'   => $d->isSameDay($now),
                    'libur' => $d->isWeekend(),
                    'title' => $l->isoFormat('dddd, D MMMM Y'),
                ];
            }
        } elseif ($jenis === 'minggu') {
            for ($c = $awal->copy(); $c->lte($akhir); $c->addDays(7)) {
                $e = $c->copy()->addDays(6)->endOfDay();
                if ($e->gt($akhir)) { $e = $akhir->copy(); }
                $l = $c->copy()->locale('id');
                $kolom[] = [
                    'label' => $l->isoFormat('D MMM'),
                    'sub'   => '',
                    'hari'  => (int) round(($e->copy()->startOfDay()->timestamp - $c->timestamp) / 86400) + 1,
                    'ini'   => $now->between($c, $e),
                    'libur' => false,
                    'title' => $l->isoFormat('D MMM Y') . ' – ' . $e->copy()->locale('id')->isoFormat('D MMM Y'),
                ];
            }
        } else {
            for ($c = $awal->copy(); $c->lte($akhir); $c = $c->copy()->startOfMonth()->addMonth()) {
                $e = $c->copy()->endOfMonth();
                if ($e->gt($akhir)) { $e = $akhir->copy(); }
                $l = $c->copy()->locale('id');
                $kolom[] = [
                    'label' => $l->isoFormat('MMM'),
                    'sub'   => '',
                    'hari'  => (int) round(($e->copy()->startOfDay()->timestamp - $c->copy()->startOfDay()->timestamp) / 86400) + 1,
                    'ini'   => $now->between($c->copy()->startOfDay(), $e),
                    'libur' => false,
                    'title' => $l->isoFormat('MMMM Y'),
                ];
            }
        }

        $lebarKolom = ['hari' => 30, 'minggu' => 72, 'bulan' => 64][$jenis];

        return [
            'awal'     => $awal,
            'akhir'    => $akhir,
            'jenis'    => $jenis,
            'kolom'    => $kolom,
            'minWidth' => 440 + count($kolom) * $lebarKolom,
            'sekarang' => ($now->between($awal, $akhir))
                ? round(($now->timestamp - $awal->timestamp) / max(1, $akhir->timestamp + 1 - $awal->timestamp) * 100, 3)
                : null,
        ];
    }

    public static function rentangBulan(int $tahun, int $bulan): array
    {
        $a = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        return self::rentang($a, $a->copy()->endOfMonth());
    }

    public static function rentangTahun(int $tahun): array
    {
        return self::rentang(Carbon::create($tahun, 1, 1), Carbon::create($tahun, 12, 31));
    }

    /**
     * Rentang otomatis untuk satu tiket: dari tanggal OPEN sampai CLOSED (atau hari ini),
     * minimal 7 hari agar grafik tidak terlalu sempit.
     */
    public static function rentangTiket(HelpTiket $tiket): array
    {
        $mulai   = Carbon::parse($tiket->created_at);
        $selesai = self::waktuTutup($tiket) ?? now();

        $awal = $mulai->copy()->startOfDay();
        if ($selesai->copy()->startOfDay()->timestamp - $awal->timestamp < 6 * 86400) {
            $awal = $selesai->copy()->startOfDay()->subDays(6);
        }

        return self::rentang($awal, $selesai);
    }

    /**
     * Posisi bar (persen) untuk interval [$a, $b] (timestamp) pada rentang. Null jika di luar rentang.
     */
    public static function posisi(int $a, int $b, array $r): ?array
    {
        $A = $r['awal']->timestamp;
        $B = $r['akhir']->timestamp + 1;
        $T = max(1, $B - $A);

        if ($b < $A || $a > $B) {
            return null;
        }

        $va    = max($a, $A);
        $vb    = min($b, $B);
        $lebar = max(0.6, ($vb - $va) / $T * 100);
        $kiri  = ($va - $A) / $T * 100;
        if ($kiri + $lebar > 100) {
            $kiri = 100 - $lebar;
        }

        return [
            'kiri'        => round($kiri, 4),
            'lebar'       => round($lebar, 4),
            'va'          => $va,
            'vb'          => $vb,
            'kiriPotong'  => $a < $A,
            'kananPotong' => $b > $B,
        ];
    }

    /**
     * Data bar satu tiket (internal).
     *
     * - CLOSED  : seluruh bar hijau (<= 7 hari) atau merah (> 7 hari)
     * - Belum   : warna per status; bagian setelah hari ke-7 diberi garis merah ('telat')
     */
    public static function barTiket(HelpTiket $tiket, array $r): ?array
    {
        $mulai   = Carbon::parse($tiket->created_at);
        $tutup   = self::waktuTutup($tiket);
        $ditutup = $tutup !== null;
        $selesai = $tutup ?? now();
        if ($selesai->lt($mulai)) {
            $selesai = $mulai->copy();
        }

        $a = $mulai->timestamp;
        $b = $selesai->timestamp;

        $pos = self::posisi($a, $b, $r);
        if (!$pos) {
            return null;
        }

        $detik = max(0, $b - $a);
        $hari  = intdiv($detik, 86400);   // hari penuh (24 jam); sisa dihitung dalam jam
        $lewat = $detik > self::BATAS_HARI * 86400;
        $hasil = $ditutup ? ($lewat ? 'bad' : 'ok') : null;

        // Fase (pengajuan, diambil/diproses, waiting, over ke GA Corp, dst) untuk SEMUA tiket,
        // termasuk yang sudah CLOSED. Garis merah = bagian setelah hari ke-7.
        $bagian = [];
        $fase   = [];
        $telat  = null;
        $durV   = max(1, $pos['vb'] - $pos['va']);

        foreach (self::segmen($tiket, $a, $b) as [$s0, $e0, $st, $ak]) {
            // rincian tiap fase: lama (tidak dipotong rentang), jam mulai (transaksi) dan pelaku
            $fase[] = ['state' => $st, 'detik' => $e0 - $s0, 'mulai' => $s0, 'oleh' => $ak];

            $s1 = max($s0, $pos['va']);
            $e1 = min($e0, $pos['vb']);
            if ($e1 > $s1) {
                $bagian[] = [
                    'state'  => $st,
                    'persen' => round(($e1 - $s1) / $durV * 100, 2),
                    'detik'  => $e0 - $s0,
                ];
            }
        }

        $tutup = null;
        if ($ditutup) {
            $logTutup = ($tiket->relationLoaded('logStatus') ? $tiket->logStatus : collect())
                ->sortBy('created_at')->last(fn ($l) => $l->status_baru === 'CLOSED');
            $tutup = ['mulai' => $b, 'oleh' => optional($logTutup)->pengguna_id];
        }

        $t7 = $a + self::BATAS_HARI * 86400;
        if ($b > $t7) {
            $ts = max($t7, $pos['va']);
            if ($pos['vb'] > $ts) {
                $telat = [
                    'kiri'  => round(($ts - $pos['va']) / $durV * 100, 2),
                    'lebar' => round(($pos['vb'] - $ts) / $durV * 100, 2),
                ];
            }
        }

        return $pos + [
            'hari'    => $hari,
            'durasi'  => self::formatDurasi($detik),
            'lebih'   => $lewat ? self::formatDurasi($detik - self::BATAS_HARI * 86400) : null,
            'lewat'   => $lewat,
            'ditutup' => (bool) $ditutup,
            'hasil'   => $hasil,
            'bagian'  => $bagian,
            'fase'    => $fase,
            'tutup'   => $tutup,
            'telat'   => $telat,
            'mulai'   => $mulai,
            'selesai' => $selesai,
        ];
    }

    /**
     * Data bar satu pengerjaan vendor.
     */
    public static function barVendor(\App\Models\help_m\HelpVendorProses $v, array $r): ?array
    {
        $mulai   = Carbon::parse($v->mulai_pada);
        $selesai = $v->selesai_pada ? Carbon::parse($v->selesai_pada) : now();

        $pos = self::posisi($mulai->timestamp, max($mulai->timestamp, $selesai->timestamp), $r);
        if (!$pos) {
            return null;
        }

        return $pos + [
            'hari'    => $v->durasi_hari,
            'durasi'  => self::formatDurasi($v->durasi_detik),
            'selesai' => $selesai,
            'mulai'   => $mulai,
            'berjalan'=> !$v->selesai_pada,
        ];
    }

    /**
     * Jejak penanganan tiket per admin, dari log status:
     * Laporan (dibuat) -> Diambil -> Eskalasi (minta bantuan GA Corp) -> Done.
     * Pengalihan PIC ke admin lain dicatat terpisah sebagai 'Dialihkan' (ALIH).
     *
     * Tiap "segmen" = satu admin memegang tiket, dari diambil sampai over / done / sekarang.
     * Admin dikelompokkan menurut NAMA (pengalihan PIC hanya mencatat nama di catatan log).
     *
     * @return array{laporan:int, diambil:?int, over:?int, done:?int, segmen:array<int,array{admin:string,mulai:int,akhir:int,tunggu:?int,hasil:string,corp:bool}>}
     */
    public static function jejakAdmin(HelpTiket $tiket, ?Carbon $sekarang = null): array
    {
        $sekarang = $sekarang ?: now();
        $laporan  = Carbon::parse($tiket->created_at)->timestamp;

        $logs = $tiket->relationLoaded('logStatus')
            ? $tiket->logStatus
            : $tiket->logStatus()->with('pengguna')->orderBy('created_at')->get();
        $logs = $logs->sortBy('created_at')->values();

        $nama   = fn ($log) => optional($log->pengguna)->nama_pelanggan ?: 'Tidak diketahui';
        $segmen = [];
        $cur    = null;          // segmen yang sedang berjalan
        $tungguDari = $laporan;  // sejak kapan tiket menunggu diambil
        $sudahOver  = false;     // sudah minta bantuan GA Corp: pengerjaan berikutnya = dikerjakan Corp
        $diambilTs = $overTs = $doneTs = null;

        $tutup = function (?array $c, int $ts, string $hasil) use (&$segmen) {
            if ($c) {
                $c['akhir'] = max($c['mulai'], $ts);
                $c['hasil'] = $hasil;
                $segmen[]   = $c;
            }

            return null;
        };

        foreach ($logs as $log) {
            $ts   = Carbon::parse($log->created_at)->timestamp;
            $lama = $log->status_lama;
            $baru = $log->status_baru;
            $cat  = (string) $log->catatan;

            if ($baru === 'HELP_GA_CORP' && $lama !== 'HELP_GA_CORP') {
                // ESKALASI: minta bantuan GA Corp (kode internal: OVER)
                $cur = $tutup($cur, $ts, 'OVER');
                $tungguDari = $ts;
                $sudahOver  = true;
                $overTs   ??= $ts;
            } elseif ($lama === $baru && str_starts_with($cat, 'Pengalihan penanggung jawab')) {
                // DIALIHKAN ke admin lain (nama admin baru ada di catatan)
                $cur = $tutup($cur, $ts, 'ALIH');
                if (preg_match('/dari .+ ke (.+?)\. Alasan:/u', $cat, $m)) {
                    $cur = ['admin' => trim($m[1]), 'mulai' => $ts, 'tunggu' => null, 'corp' => $sudahOver];
                }
            } elseif ($baru === 'ON_PROCESS' && !$cur) {
                // DIAMBIL
                $cur = ['admin' => $nama($log), 'mulai' => $ts, 'tunggu' => max(0, $ts - $tungguDari), 'corp' => $sudahOver];
                $diambilTs ??= $ts;
            } elseif ($baru === 'DONE') {
                $cur = $tutup($cur, $ts, 'DONE');
                $doneTs = $ts;
            } elseif ($baru === 'CLOSED') {
                $cur = $tutup($cur, $ts, 'CLOSED');
            }
        }

        if ($cur) {
            // Log DONE/CLOSED tidak tercatat, padahal tiketnya sudah DONE/CLOSED:
            // akhiri di waktu selesai/tutup tiket, jangan dianggap masih berjalan sampai sekarang.
            $akhirTiket = in_array($tiket->status, ['DONE', 'CLOSED'], true) ? self::waktuSelesai($tiket) : null;

            if ($akhirTiket) {
                $akhirTs = max($cur['mulai'], $akhirTiket->timestamp);
                $cur     = $tutup($cur, $akhirTs, 'DONE');
                $doneTs  = $akhirTs;
            } else {
                $cur = $tutup($cur, $sekarang->timestamp, 'BERJALAN');
            }
        }

        // Tiket lama tanpa log lengkap: pakai kolom ditugaskan_ke / diproses_pada
        if (!$segmen && $tiket->ditugaskan_ke && $tiket->diproses_pada) {
            $mulai = Carbon::parse($tiket->diproses_pada)->timestamp;
            $akhir = self::waktuSelesai($tiket);
            $segmen[] = [
                'admin'  => optional($tiket->ditugaskanKe)->nama_pelanggan ?: 'Tidak diketahui',
                'mulai'  => $mulai,
                'akhir'  => max($mulai, ($akhir ?: $sekarang)->timestamp),
                'tunggu' => max(0, $mulai - $laporan),
                'hasil'  => $akhir ? 'DONE' : 'BERJALAN',
                'corp'   => false,
            ];
            $diambilTs = $mulai;
            $doneTs    = $akhir ? $akhir->timestamp : null;
        }

        return [
            'laporan' => $laporan,
            'diambil' => $diambilTs,   // pertama kali diambil
            'over'    => $overTs,      // pertama kali minta bantuan GA Corp
            'done'    => $doneTs,
            'segmen'  => $segmen,
        ];
    }

    /**
     * Rekap waktu per admin untuk sekumpulan tiket (biasanya satu Business Unit).
     * Semua angka dalam DETIK; $rinci = true menyertakan daftar tiket tiap admin.
     */
    public static function rekapAdmin(iterable $tikets, ?Carbon $sekarang = null, bool $rinci = false): array
    {
        $avg   = fn (array $a) => $a ? (int) round(array_sum($a) / count($a)) : null;
        $admin = [];
        $tunggu = $over = $totalDone = $bagBU = $bagCorp = [];
        $nTiket = $nBU = $nCorp = 0;
        $label  = ['DONE' => 'Done', 'OVER' => 'Ke GA Corp', 'ALIH' => 'Dialihkan', 'BERJALAN' => 'Berjalan', 'CLOSED' => 'Closed'];
        $fmt    = fn (int $ts) => Carbon::createFromTimestamp($ts)->format('d M H:i');

        foreach ($tikets as $t) {
            $j = self::jejakAdmin($t, $sekarang);
            if (!$j['segmen']) {
                continue;
            }
            $nTiket++;

            // siapa yang mengerjakan tiket ini: GA Corp (pernah diteruskan / dikerjakan Corp) atau BU sendiri
            $korp = $j['over'] !== null || (bool) array_filter($j['segmen'], fn ($sg) => !empty($sg['corp']));
            $korp ? $nCorp++ : $nBU++;

            // Diambil -> Done per TIKET, dipisah: bagian dikerjakan BU (sebelum eskalasi) dan dikerjakan GA Corp (setelah eskalasi)
            if ($j['diambil'] !== null && $j['done'] !== null && $j['done'] >= $j['diambil']) {
                $ov = $j['over'] !== null ? min(max($j['over'], $j['diambil']), $j['done']) : null;
                $totalDone[] = $j['done'] - $j['diambil'];
                $bagBU[]     = ($ov ?? $j['done']) - $j['diambil'];
                $bagCorp[]   = $ov !== null ? $j['done'] - $ov : 0;
            }

            foreach ($j['segmen'] as $s) {
                $detik = $s['akhir'] - $s['mulai'];
                $nm    = $s['admin'];
                $admin[$nm] ??= ['nama' => $nm, 'corp' => false, 'tiket' => [], 'tunggu' => [], 'over' => [], 'done' => [], 'berjalan' => 0, 'daftar' => []];
                $admin[$nm]['corp'] = $admin[$nm]['corp'] || !empty($s['corp']);

                $admin[$nm]['tiket'][$t->id] = true;
                if ($s['tunggu'] !== null) {
                    $admin[$nm]['tunggu'][] = $s['tunggu'];
                    $tunggu[] = $s['tunggu'];
                }
                if ($s['hasil'] === 'DONE') {
                    $admin[$nm]['done'][] = $detik;
                } elseif ($s['hasil'] === 'OVER') {
                    $admin[$nm]['over'][] = $detik;
                    $over[] = $detik;
                } elseif ($s['hasil'] === 'BERJALAN') {
                    $admin[$nm]['berjalan']++;
                }

                if ($rinci) {
                    $admin[$nm]['daftar'][] = [
                        'nomor'   => $t->nomor_tiket,
                        'judul'   => $t->judul,
                        'url'     => route('help.proses.show', $t),
                        'laporan' => $fmt($j['laporan']),
                        'diambil' => $fmt($s['mulai']),
                        'tunggu'  => $s['tunggu'] !== null ? self::formatDurasi($s['tunggu']) : null,
                        'proses'  => self::formatDurasi($detik),
                        'hasil'   => $label[$s['hasil']] ?? $s['hasil'],
                        'kode'    => $s['hasil'],
                        'akhir'   => $fmt($s['akhir']),
                    ];
                }
            }
        }

        $baris = array_map(fn ($a) => [
            'nama'     => $a['nama'],
            'corp'     => $a['corp'],
            'tiket'    => count($a['tiket']),
            'tunggu'   => $avg($a['tunggu']),
            'over'     => $avg($a['over']),
            'done'     => $avg($a['done']),
            'jml_over' => count($a['over']),
            'jml_done' => count($a['done']),
            'berjalan' => $a['berjalan'],
            'daftar'   => $a['daftar'],
        ], array_values($admin));
        usort($baris, fn ($x, $y) => [$y['tiket'], $x['nama']] <=> [$x['tiket'], $y['nama']]);

        return [
            'tiket'    => $nTiket,
            'tiket_bu'   => $nBU,
            'tiket_corp' => $nCorp,
            'tunggu'   => $avg($tunggu),
            'over'     => $avg($over),
            'done'     => $avg($totalDone),   // Diambil -> Done per tiket (BU + GA Corp)
            'done_bu'  => $avg($bagBU),
            'done_corp'=> $avg($bagCorp),
            'jml_over' => count($over),
            'jml_done' => count($totalDone),
            'admin'    => $baris,
        ];
    }

    /**
     * Susun segmen [awal, akhir, status] dari log status.
     */
    public static function segmen(HelpTiket $tiket, int $awal, int $akhir): array
    {
        $logs = $tiket->relationLoaded('logStatus')
            ? $tiket->logStatus
            : $tiket->logStatus()->orderBy('created_at')->get();

        $logs   = $logs->sortBy('created_at')->values();
        $state  = 'OPEN';
        $titik  = $awal;
        $segmen = [];
        $berubah = false;
        $sudahOver = false;   // sudah pernah di-over ke GA Corp / unit lain
        $aktor     = $tiket->pelapor_id;   // siapa yang memulai fase berjalan (pengaju untuk fase OPEN)

        foreach ($logs as $log) {
            $baru = $log->status_baru;
            if (!$baru || $baru === $state) {
                continue;
            }
            if ($baru === 'CLOSED') {
                break;
            }

            // Setelah di-over, pengerjaan oleh GA Corp dibedakan dari pengerjaan unit awal
            if ($baru === 'HELP_GA_CORP') {
                $sudahOver = true;
            } elseif ($sudahOver && $baru === 'ON_PROCESS') {
                $baru = 'ON_PROCESS_CORP';
            }
            if ($baru === $state) {
                continue;
            }

            $ts = max($awal, min(Carbon::parse($log->created_at)->timestamp, $akhir));
            if ($ts > $titik) {
                $segmen[] = [$titik, $ts, $state, $aktor];
            }
            $titik   = $ts;
            $state   = $baru;
            $aktor   = $log->pengguna_id;
            $berubah = true;
        }

        // Fallback untuk tiket lama yang log statusnya tidak lengkap
        if (!$berubah && $tiket->status !== 'OPEN') {
            $akhirStatus = $tiket->status === 'CLOSED' ? 'ON_PROCESS' : $tiket->status;
            $pisah = $tiket->diproses_pada ? Carbon::parse($tiket->diproses_pada)->timestamp : null;

            if ($pisah && $pisah > $awal && $pisah < $akhir) {
                $segmen[] = [$awal, $pisah, 'OPEN', $tiket->pelapor_id];
                $titik    = $pisah;
            }
            $state = $akhirStatus;
            $aktor = $tiket->ditugaskan_ke;
        }

        if ($akhir > $titik) {
            $segmen[] = [$titik, $akhir, $state, $aktor];
        }

        return $segmen;
    }

    /**
     * Waktu tiket ditutup. Null bila tiket belum CLOSED.
     *
     * Tiket lama berstatus CLOSED sering tidak punya ditutup_pada, maka dipakai cadangan:
     * ditutup_pada -> diselesaikan_pada -> updated_at. (Tanpa ini tiket CLOSED dianggap
     * masih berjalan dan digambar sampai hari ini.)
     */
    public static function waktuTutup(HelpTiket $tiket): ?Carbon
    {
        if ($tiket->status !== 'CLOSED') {
            return null;
        }

        $t = $tiket->ditutup_pada ?: ($tiket->diselesaikan_pada ?: $tiket->updated_at);

        return Carbon::parse($t ?: $tiket->created_at);
    }

    /**
     * Waktu pengerjaan tiket selesai (status DONE), bukan waktu ditutup.
     * Urutan: log status DONE terakhir -> diselesaikan_pada -> (tiket lama CLOSED tanpa data DONE) waktu tutup.
     * Null bila tiket belum pernah DONE.
     */
    public static function waktuSelesai(HelpTiket $tiket): ?Carbon
    {
        $logs = $tiket->relationLoaded('logStatus')
            ? $tiket->logStatus
            : $tiket->logStatus()->orderBy('created_at')->get();

        $log = $logs->sortBy('created_at')->last(fn ($l) => $l->status_baru === 'DONE');
        if ($log) {
            return Carbon::parse($log->created_at);
        }

        if ($tiket->diselesaikan_pada) {
            return Carbon::parse($tiket->diselesaikan_pada);
        }

        return self::waktuTutup($tiket);
    }

    /**
     * Waktu tiket pertama kali diambil (status pertama kali ON_PROCESS).
     * Selisih dengan created_at = lama menunggu diambil sejak pengajuan.
     */
    public static function waktuDiambil(HelpTiket $tiket): ?Carbon
    {
        $logs = $tiket->relationLoaded('logStatus')
            ? $tiket->logStatus
            : $tiket->logStatus()->orderBy('created_at')->get();

        $log = $logs->sortBy('created_at')->first(fn ($l) => $l->status_baru === 'ON_PROCESS');
        if ($log) {
            return Carbon::parse($log->created_at);
        }

        // tiket lama tanpa log lengkap
        if ($tiket->diproses_pada) {
            return Carbon::parse($tiket->diproses_pada);
        }

        return null;
    }

    /**
     * Warna Business Unit (dikenali dari nama) dan urutan tampil.
     * Corporation = coklat, Cement = merah, Property = biru, Plantations = hijau, Downstream = orange.
     */
    public const WARNA_CORP = '#92400e';

    public static function infoBisnisUnit(?string $nama): array
    {
        $n = strtolower((string) $nama);

        foreach ([
            ['cement',     '#dc2626', 2],
            ['property',   '#2563eb', 3],
            ['plantation', '#16a34a', 4],
            ['downstream', '#f97316', 5],
            ['corp',       self::WARNA_CORP, 1],
        ] as [$kata, $warna, $urut]) {
            if (str_contains($n, $kata)) {
                return ['warna' => $warna, 'urut' => $urut];
            }
        }

        return ['warna' => '#3b82f6', 'urut' => 99];
    }
}