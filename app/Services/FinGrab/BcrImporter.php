<?php

namespace App\Services\FinGrab;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlDate;

/**
 * Alur: baca xlsx BCR -> bersihkan ID -> cocokkan ke api_emp_hcis (ID, lalu email)
 * -> tentukan Bisnis unit dari PT penagih -> simpan snapshot (per Booking ID + Trip code).
 * Upload ulang file yang sama memperbarui baris, tidak menggandakan. Satu booking dengan beberapa trip disimpan sebagai beberapa baris.
 */
class BcrImporter
{
    /** Baris tanpa BILLING_TYPE (dan baris lama sebelum kolom ada) dianggap transaksi biasa. */
    public const BT_DEFAULT = 'corp bill transaction';

    private array $ptMap;
    private array $gcMap = [];
    private int $filesRead = 0;

    public function __construct()
    {
        // keyword terpanjang dicek lebih dulu
        $map = DB::table('fingrab_pt_bisnis_unit')->pluck('id_bisnis_unit', 'keyword')->all();
        uksort($map, fn ($a, $b) => strlen($b) <=> strlen($a));
        $this->ptMap = $map;

        // Pemetaan group company HCIS -> Bisnis unit. Default: nama group company sama dengan nama Bisnis unit
        // (Cement, Property, Plantations, Downstream, KPN Corporation). Pengecualian diatur di
        // config('fingrab.group_company_map') => ['KPN Sugar' => 'Plantations'].
        $bus = DB::table('tb_bisnis_unit')->pluck('id_bisnis_unit', 'nama_bisnis_unit')->all();
        foreach ($bus as $name => $id) {
            $this->gcMap[Str::lower(trim((string) $name))] = (int) $id;
        }
        foreach ((array) config('fingrab.group_company_map', []) as $gc => $buName) {
            $id = $bus[$buName] ?? null;
            if ($id !== null) {
                $this->gcMap[Str::lower(trim((string) $gc))] = (int) $id;
            }
        }
    }

    /**
     * @param string[] $paths  path file xlsx
     * @param string[] $names  nama asli file (untuk catatan batch)
     */
    public function import(array $paths, array $names, ?int $userId): array
    {
        $rows = [];
        foreach ($paths as $path) {
            foreach ($this->readSheet($path) as $r) {
                $rows[] = $r;
            }
        }

        $skipped = 0;
        $clean = [];
        foreach ($rows as $r) {
            $booking = trim((string) ($r['BOOKING_ID'] ?? ''));
            if ($booking === '') {
                $skipped++;
                continue;
            }
            [$date, $hour] = $this->parseTime($r['TRANSACTION_TIME'] ?? null);
            if ($date === null) {
                $skipped++;
                continue;
            }
            [$id, $status, $hint] = $this->cleanId($r['EMPLOYEE_ID'] ?? null);
            $clean[] = [
                'booking' => $booking, 'trip' => trim((string) ($r['TRIP_CODE'] ?? '')), 'date' => $date, 'hour' => $hour, 'id' => $id, 'status' => $status,
                'hint' => $hint, 'mail' => Str::lower(trim((string) ($r['EMPLOYEE_EMAIL_ADDRESS'] ?? ''))),
                'name' => Str::lower(trim((string) ($r['EMPLOYEE_NAME'] ?? ''))), 'r' => $r,
                'bt' => $this->billingType($r['BILLING_TYPE'] ?? null),
            ];
        }

        $clean = $this->applyIdOverrides($clean);
        $hcis = $this->loadHcis($clean);

        return DB::transaction(function () use ($clean, $hcis, $names, $userId, $skipped) {
            $batchId = DB::table('fingrab_batches')->insertGetId([
                'uploaded_by' => $userId, 'files' => implode(', ', $names),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $now = now();
            $records = [];
            $stat = ['id' => 0, 'email' => 0, 'none' => 0, 'pt_unmapped' => 0];
            $unmappedPt = [];
            $dates = [];

            foreach ($clean as $c) {
                $r = $c['r'];
                $emp = null;
                $match = 'none';
                if ($c['id'] && isset($hcis['id'][$c['id']])) {
                    $emp = $hcis['id'][$c['id']];
                    $match = 'id';
                } elseif ($c['mail'] !== '' && isset($hcis['mail'][$c['mail']])) {
                    $emp = $hcis['mail'][$c['mail']];
                    $match = 'email';
                }
                $stat[$match]++;

                $pt = trim((string) ($r['COMPANY_NAME'] ?? ''));
                // Prioritas: group company karyawan di HCIS; bila tidak cocok / tidak terpetakan, pakai PT penagih.
                $bu = $this->buForGroupCompany($emp->group_company ?? null) ?? $this->buForPt($pt);
                if ($bu === null) {
                    $stat['pt_unmapped']++;
                    $unmappedPt[$pt] = true;
                }
                $dates[] = $c['date'];

                $records[] = [
                    'batch_id' => $batchId,
                    'booking_id' => $c['booking'],
                    'trip_code' => Str::limit($c['trip'], 100, ''),
                    'trx_date' => $c['date'],
                    'trx_hour' => $c['hour'],
                    'vertical' => Str::lower(trim((string) ($r['VERTICAL'] ?? ''))),
                    'billing_type' => $c['bt'],
                    'pt_name' => $pt,
                    'id_bisnis_unit' => $bu,
                    'employee_id_raw' => Str::limit(trim((string) ($r['EMPLOYEE_ID'] ?? '')), 100, ''),
                    'employee_id' => $c['id'],
                    'employee_id_status' => $c['status'],
                    'employee_id_hint' => Str::limit($c['hint'], 100, ''),
                    'employee_name' => trim((string) ($r['EMPLOYEE_NAME'] ?? '')),
                    'employee_email' => $c['mail'] ?: null,
                    'hcis_match' => $match,
                    'hcis_group_company' => $emp->group_company ?? null,
                    'hcis_unit' => $emp->unit ?? null,
                    'hcis_office_area' => $emp->office_area ?? null,
                    'group_name' => $this->text($r['GROUP_NAME'] ?? null),
                    'city' => $this->text($r['CITY'] ?? null),
                    'pickup' => $this->text($r['PICK_UP'] ?? null),
                    'dropoff' => $this->text($r['DROP_OFF'] ?? null),
                    'intermediate' => $this->text($r['INTERMEDIATE_DROPOFF'] ?? null),
                    'distance_km' => is_numeric($r['DISTANCE'] ?? null) ? round((float) $r['DISTANCE'], 2) : null,
                    'trip_description' => $this->text($r['TRIP_DESCRIPTION'] ?? null),
                    'taxi_type' => $this->text($r['TAXI_TYPE'] ?? null),
                    'amount' => $this->money($r['AMOUNT'] ?? null) ?? 0,
                    'base_fare' => $this->money($r['BASE_FARE'] ?? null),
                    'promo' => $this->money($r['PROMO'] ?? null),
                    'toll' => $this->money($r['TOLLS_AND_SURCHARGE'] ?? null),
                    'late_fee' => $this->money($r['LATE_FEES'] ?? null),
                    'other_fee' => $this->money($r['OTHER_FEES'] ?? null),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Satu baris per Booking ID + Trip code + Billing type. Refund dan Tip memakai Booking ID dan Trip code
            // yang sama dengan transaksi aslinya, jadi Billing type wajib ikut jadi kunci. Tanpa itu refund menimpa
            // transaksi (nominal terpotong dua kali) dan tip hilang. Hanya baris yang kembar persis digabung, baris terakhir dipakai.
            $byBooking = [];
            $duplicates = 0;
            foreach ($records as $rec) {
                $k = $this->rowKey($rec['booking_id'], $rec['trip_code'], $rec['billing_type']);
                if (isset($byBooking[$k])) {
                    $duplicates++;
                }
                $byBooking[$k] = $rec;
            }
            $records = array_values($byBooking);

            // Bandingkan dengan data yang sudah ada:
            //  - belum ada            -> ditambahkan
            //  - ada, isi berubah     -> hanya kolom yang berubah yang diperbarui (batch_id dan updated_at ikut diperbarui)
            //  - ada, isi sama        -> tidak disentuh sama sekali
            // Baris yang tidak ada di file ini tidak dihapus.
            $skip = ['booking_id', 'trip_code', 'batch_id', 'created_at', 'updated_at'];
            $claimed = [];
            $cnt = ['new' => 0, 'updated' => 0, 'unchanged' => 0];
            $fresh = [];

            foreach (array_chunk($records, 500) as $chunk) {
                $existing = DB::table('fingrab_transactions')
                    ->whereIn('booking_id', array_column($chunk, 'booking_id'))
                    ->get()
                    ->keyBy(fn ($row) => $this->rowKey((string) $row->booking_id, (string) ($row->trip_code ?? ''), $row->billing_type ?? null));

                foreach ($chunk as $rec) {
                    $old = $existing->get($this->rowKey($rec['booking_id'], $rec['trip_code'], $rec['billing_type']));

                    // Baris lama (dibuat sebelum ada Trip code) diambil alih oleh trip pertama dari booking yang sama.
                    $legacy = false;
                    if ($old === null && $rec['trip_code'] !== '') {
                        $lk = $this->rowKey($rec['booking_id'], '', $rec['billing_type']);
                        if (!isset($claimed[$lk]) && $existing->has($lk)) {
                            $old = $existing->get($lk);
                            $claimed[$lk] = true;
                            $legacy = true;
                        }
                    }

                    if ($old === null) {
                        $fresh[] = $rec;
                        $cnt['new']++;
                        continue;
                    }

                    $diff = $legacy ? ['trip_code' => $rec['trip_code']] : [];
                    foreach ($rec as $col => $val) {
                        if (in_array($col, $skip, true)) {
                            continue;
                        }
                        if (!$this->sameValue($col, $val, $old->{$col} ?? null)) {
                            $diff[$col] = $val;
                        }
                    }

                    if ($diff) {
                        DB::table('fingrab_transactions')->where('id', $old->id)
                            ->update($diff + ['batch_id' => $batchId, 'updated_at' => $now]);
                        $cnt['updated']++;
                    } else {
                        $cnt['unchanged']++;
                    }
                }
            }

            foreach (array_chunk($fresh, 200) as $chunk) {
                DB::table('fingrab_transactions')->insert($chunk);
            }

            $summary = [
                'files' => $this->filesRead,
                'total' => count($records),
                'new' => $cnt['new'],
                'updated' => $cnt['updated'],
                'unchanged' => $cnt['unchanged'],
                'duplicates' => $duplicates,
                'matched_id' => $stat['id'],
                'matched_email' => $stat['email'],
                'unmatched' => $stat['none'],
                'pt_unmapped' => $stat['pt_unmapped'],
                'pt_unmapped_names' => array_keys($unmappedPt),
                'skipped' => $skipped,
                'period_from' => $dates ? min($dates) : null,
                'period_to' => $dates ? max($dates) : null,
            ];

            DB::table('fingrab_batches')->where('id', $batchId)->update([
                'period_from' => $summary['period_from'], 'period_to' => $summary['period_to'],
                'rows_total' => $summary['total'], 'rows_matched' => $stat['id'] + $stat['email'],
                'rows_unmatched' => $stat['none'], 'rows_pt_unmapped' => $stat['pt_unmapped'],
                'updated_at' => now(),
            ]);

            return $summary;
        });
    }

    /** Baris sheet pertama sebagai array berkunci nama kolom (huruf besar). */
    private function readSheet(string $path): \Generator
    {
        $data = IOFactory::load($path)->getActiveSheet()->toArray(null, true, false, false);
        $head = array_map(fn ($h) => Str::upper(trim((string) $h)), array_shift($data) ?? []);
        if (!in_array('BOOKING_ID', $head, true)) {
            return; // bukan file BCR
        }
        $this->filesRead++;

        foreach ($data as $line) {
            if (!array_filter($line, fn ($v) => $v !== null && $v !== '')) {
                continue;
            }
            $row = [];
            foreach ($head as $i => $h) {
                if ($h !== '') {
                    $row[$h] = $line[$i] ?? null;
                }
            }
            yield $row;
        }
    }

    /** @return array{0: ?string, 1: int} [Y-m-d, jam] */
    private function parseTime($v): array
    {
        if (is_numeric($v)) {
            $d = XlDate::excelToDateTimeObject((float) $v);
            return [$d->format('Y-m-d'), (int) $d->format('H')];
        }
        if (preg_match('/^(\d{4}-\d{2}-\d{2})[ T](\d{2})/', trim((string) $v), $m)) {
            return [$m[1], (int) $m[2]];
        }
        return [null, 0];
    }

    /**
     * Rapikan ID karyawan dari BCR (diisi manual oleh pemesan):
     *  - 11-12 digit: valid. 10 digit: angka 0 di depan hilang, ditambahkan.
     *  - "01115020001 - Corp": akhiran dibuang. Email, teks bebas, atau kosong: tidak ada ID.
     * @return array{0: ?string, 1: string, 2: string} [id, status, keterangan]
     */
    private function cleanId($raw): array
    {
        if (is_int($raw) || is_float($raw)) {
            $raw = sprintf('%.0f', $raw);
        }
        $raw = trim(str_replace("\t", ' ', (string) $raw));

        if (preg_match('/^\d{11,12}$/', $raw)) {
            return [$raw, 'valid', ''];
        }
        if (preg_match('/^\d{10}$/', $raw)) {
            return ['0' . $raw, 'padded', ''];
        }
        if (preg_match('/^(\d{10,12})\s*-\s*(.*)$/', $raw, $m)) {
            return [strlen($m[1]) === 10 ? '0' . $m[1] : $m[1], 'suffix', trim($m[2])];
        }
        if (str_contains($raw, '@')) {
            return [null, 'email', ''];
        }
        if ($raw !== '') {
            return [null, 'teks', $raw];
        }
        return [null, 'kosong', ''];
    }

    /** Ambil dari HCIS hanya 6 kolom yang diperlukan, hanya untuk ID/email yang ada di file. */
    private function loadHcis(array $clean): array
    {
        $cols = ['employee_id', 'fullname', 'email', 'group_company', 'office_area', 'unit'];
        $ids = collect($clean)->pluck('id')->filter()->unique()->values();
        $mails = collect($clean)->pluck('mail')->filter()->unique()->values();
        $byId = [];
        $byMail = [];

        foreach ($ids->chunk(500) as $c) {
            foreach (DB::table('api_emp_hcis')->select($cols)->whereIn('employee_id', $c->all())->get() as $e) {
                $byId[trim($e->employee_id)] = $e;
            }
        }
        foreach ($mails->chunk(500) as $c) {
            foreach (DB::table('api_emp_hcis')->select($cols)->whereIn('email', $c->all())->get() as $e) {
                $byMail[Str::lower(trim((string) $e->email))] = $e;
            }
        }

        return ['id' => $byId, 'mail' => $byMail];
    }

    /**
     * Koreksi ID karyawan manual (halaman Kecocokan data) diterapkan sebelum pencocokan HCIS.
     * Urutan identitas sama dengan dashboard: ID, lalu email, lalu nama.
     */
    private function applyIdOverrides(array $clean): array
    {
        $ov = ['id' => [], 'email' => [], 'name' => []];
        foreach (DB::table('fingrab_id_overrides')->get() as $o) {
            $ov[$o->match_type][$o->match_value] = $o->employee_id;
        }
        if (!array_filter($ov)) {
            return $clean;
        }

        foreach ($clean as &$c) {
            $fix = null;
            if ($c['id']) {
                $fix = $ov['id'][$c['id']] ?? null;
            } elseif ($c['mail'] !== '') {
                $fix = $ov['email'][$c['mail']] ?? null;
            } elseif ($c['name'] !== '') {
                $fix = $ov['name'][$c['name']] ?? null;
            }
            if ($fix) {
                $c['id'] = $fix;
                $c['status'] = 'manual';
                $c['hint'] = '';
            }
        }
        unset($c);

        return $clean;
    }

    /** Kolom angka dibandingkan sebagai angka; kolom lain sebagai teks persis. Kosong (null atau '') dianggap sama. */
    private const NUMERIC_COLS = ['trx_hour', 'id_bisnis_unit', 'distance_km', 'amount', 'base_fare', 'promo', 'toll', 'late_fee', 'other_fee'];

    private function sameValue(string $col, $new, $old): bool
    {
        $newEmpty = $new === null || $new === '';
        $oldEmpty = $old === null || $old === '';
        if ($newEmpty || $oldEmpty) {
            return $newEmpty && $oldEmpty;
        }
        if (in_array($col, self::NUMERIC_COLS, true)) {
            return abs((float) $new - (float) $old) < 0.005;
        }
        return (string) $new === (string) $old;
    }

    private function buForGroupCompany(?string $gc): ?int
    {
        $k = Str::lower(trim((string) $gc));
        return $k !== '' && isset($this->gcMap[$k]) ? $this->gcMap[$k] : null;
    }

    private function buForPt(?string $pt): ?int
    {
        $t = Str::lower($pt ?? '');
        foreach ($this->ptMap as $keyword => $idBu) {
            if ($keyword !== '' && str_contains($t, Str::lower($keyword))) {
                return (int) $idBu;
            }
        }
        return null;
    }

    private function billingType($v): string
    {
        $t = Str::lower(trim(preg_replace('/\s+/', ' ', (string) $v)));
        return $t === '' ? self::BT_DEFAULT : Str::limit($t, 50, '');
    }

    private function rowKey(string $booking, string $trip, ?string $bt): string
    {
        $bt = Str::lower(trim((string) $bt));
        return Str::lower($booking) . '|' . Str::lower($trip) . '|' . ($bt === '' ? self::BT_DEFAULT : $bt);
    }

    private function text($v): ?string
    {
        $s = trim(preg_replace('/\s+/', ' ', (string) $v));
        return $s === '' ? null : $s;
    }

    private function money($v): ?int
    {
        return ($v === null || $v === '' || !is_numeric($v)) ? null : (int) round((float) $v);
    }
}