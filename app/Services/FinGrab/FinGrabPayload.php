<?php

namespace App\Services\FinGrab;

use App\Models\FinGrabm\FinGrabTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Menyusun data untuk halaman (struktur X yang dipakai dashboard).
 * Pemotongan akses per Bisnis unit terjadi di sini, di query, sebelum data keluar dari server.
 *
 * Ringan: hasil di-cache per scope (data hanya berubah saat upload BCR atau koreksi ID, panggil bust() di sana),
 * kolom yang diambil hanya yang dipakai grafik/tabel, dan detail transaksi (alamat, rincian tarif, booking)
 * TIDAK ikut ke halaman; diambil per 10 baris lewat detail().
 */
class FinGrabPayload
{
    private const VERTICAL = ['food' => 0, 'transport' => 1, 'express' => 2];
    private const VER_KEY = 'fingrab:payload:ver';
    private const TTL = 21600; // 6 jam, jaga-jaga bila data HCIS berubah tanpa upload

    /** Panggil setelah upload BCR atau koreksi ID karyawan supaya cache dibuat ulang. */
    public static function bust(): void
    {
        Cache::forever(self::VER_KEY, (int) Cache::get(self::VER_KEY, 1) + 1);
    }

    public static function build(array $scope): array
    {
        $ids = $scope['all'] ? [] : collect($scope['ids'])->sort()->values()->all();
        $key = 'fingrab:payload:v' . (int) Cache::get(self::VER_KEY, 1) . ':' . ($scope['all'] ? 'all' : implode(',', $ids));

        return Cache::remember($key, self::TTL, fn () => self::compute($scope));
    }

    private static function compute(array $scope): array
    {
        $q = FinGrabTransaction::query()
            ->select([
                'id', 'trx_date', 'pt_name', 'group_name', 'vertical', 'amount', 'trx_hour', 'city',
                'booking_id', 'trip_code', 'billing_type', 'id_bisnis_unit',
                'employee_id', 'employee_name', 'employee_email', 'employee_id_status', 'employee_id_hint',
                'hcis_group_company', 'hcis_unit', 'hcis_office_area', 'hcis_match',
            ])
            ->orderBy('trx_date')->orderBy('id');
        if (!$scope['all']) {
            $q->whereIn('id_bisnis_unit', $scope['ids']); // PT belum terpetakan (null) hanya terlihat oleh Corporate
        }
        $all = $q->get();

        // Tip dan Refund memakai Booking ID + Trip code transaksi aslinya. Supaya nominal dashboard = nominal bersih
        // (transaksi + tip + refund) dan jumlah transaksi tetap hanya transaksi, keduanya dilipat ke baris transaksi
        // di bulan yang sama. Bila tidak ada transaksi di bulan itu (mis. refund bulan berikutnya), tampil sebagai baris sendiri.
        [$all, $extra] = self::foldAdjustments($all);

        $buNames = DB::table('tb_bisnis_unit')->pluck('nama_bisnis_unit', 'id_bisnis_unit');
        $hcisNames = self::hcisNames($all->pluck('employee_id'));
        $hcisByEmail = self::hcisNamesByEmail($all->filter(fn ($t) => !$t->employee_id)->pluck('employee_email'));

        $co = $all->pluck('pt_name')->filter()->unique()->sort()->values()->all();
        $gr = $all->pluck('group_name')->filter()->unique()->sort()->values()->all();
        $ci = $all->pluck('city')->map(fn ($c) => $c ?: '-')->unique()->sort()->values()->all();
        $coIx = array_flip($co);
        $grIx = array_flip($gr);
        $ciIx = array_flip($ci);

        $em = [];
        $emIx = [];
        $rows = [];

        foreach ($all as $t) {
            $key = $t->employee_id ?: ($t->employee_email ? '@' . $t->employee_email : '#' . mb_strtolower((string) $t->employee_name));
            if (!isset($emIx[$key])) {
                $emIx[$key] = count($em);
                $em[] = [
                    $t->employee_id ?? '', $t->employee_name ?? '', $t->employee_email ?? '',
                    $t->employee_id_status ?? 'kosong', $t->employee_id_hint ?? '',
                    $t->hcis_group_company, $t->hcis_unit, $t->hcis_office_area, $t->hcis_match,
                    // Elemen ke-10: nama lengkap di HCIS. Kosong bila tidak ada di HCIS; dashboard lalu memakai nama Grab.
                    $hcisNames[(string) $t->employee_id] ?? ($hcisByEmail[mb_strtolower((string) $t->employee_email)] ?? ''),
                ];
            }

            // Baris slim: [tanggal, PT, group, karyawan, tipe, nominal, jam, kota, id transaksi, Bisnis unit]
            $rows[] = [
                $t->trx_date->format('Y-m-d'),
                $coIx[$t->pt_name] ?? 0,
                $grIx[$t->group_name] ?? 0,
                $emIx[$key],
                self::VERTICAL[$t->vertical] ?? 0,
                (int) $t->amount + (int) ($extra[$t->id] ?? 0),
                (int) $t->trx_hour,
                $ciIx[$t->city ?: '-'] ?? 0,
                (int) $t->id,
                $t->id_bisnis_unit ? ($buNames[$t->id_bisnis_unit] ?? 'Belum terpetakan') : 'Belum terpetakan',
            ];
        }

        return ['co' => $co, 'gr' => $gr, 'ci' => $ci, 'em' => $em, 'rows' => $rows];
    }

    /**
     * Detail transaksi untuk panel samping dan popup (maks 50 id per panggilan), tetap dipotong per Bisnis unit.
     *
     * @param  array<int,int|string>  $ids
     * @return array<int,array{k:string,x:array}> id transaksi => [booking, [pickup, dropoff, singgah, jarak, keterangan, taksi, dasar, promo, tol, late, lain, trip code]]
     */
    public static function detail(array $scope, array $ids): array
    {
        $ids = collect($ids)->map(fn ($i) => (int) $i)->filter()->unique()->take(50)->values()->all();
        if (!$ids) {
            return [];
        }

        $q = FinGrabTransaction::query()->whereIn('id', $ids);
        if (!$scope['all']) {
            $q->whereIn('id_bisnis_unit', $scope['ids']);
        }

        $out = [];
        foreach ($q->get() as $t) {
            $out[$t->id] = [
                'k' => (string) $t->booking_id,
                'x' => [
                    $t->pickup ?? '', $t->dropoff ?? '', $t->intermediate ?? '', (float) $t->distance_km,
                    $t->trip_description ?? '', $t->taxi_type ?? '',
                    $t->base_fare, $t->promo, $t->toll, $t->late_fee, $t->other_fee,
                    $t->trip_code ?? '',
                ],
            ];
        }

        return $out;
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: array<int,int>} [baris tanpa tip/refund yang terlipat, id transaksi => total tip+refund]
     */
    private static function foldAdjustments($all): array
    {
        $def = BcrImporter::BT_DEFAULT;
        $key = fn ($t) => mb_strtolower((string) $t->booking_id) . '|' . mb_strtolower((string) ($t->trip_code ?? ''));
        $bt = fn ($t) => ($t->billing_type ?? '') === '' ? $def : $t->billing_type;
        $ym = fn ($t) => $t->trx_date->format('Y-m');

        $base = [];
        foreach ($all as $t) {
            if ($bt($t) === $def) {
                $base[$key($t) . '|' . $ym($t)] ??= $t->id;
            }
        }

        $extra = [];
        $folded = [];
        foreach ($all as $t) {
            if ($bt($t) === $def) {
                continue;
            }
            $baseId = $base[$key($t) . '|' . $ym($t)] ?? null;
            if ($baseId !== null) {
                $extra[$baseId] = ($extra[$baseId] ?? 0) + (int) $t->amount;
                $folded[$t->id] = true;
            }
        }

        return [$all->reject(fn ($t) => isset($folded[$t->id]))->values(), $extra];
    }

    /**
     * Nama lengkap HCIS untuk karyawan yang tidak punya employee_id di transaksi tetapi cocok lewat email.
     * Dipisah dari hcisNames() supaya kolom email yang salah hanya menghilangkan nama jenis ini.
     *
     * @return array<string,string> email huruf kecil => fullname
     */
    private static function hcisNamesByEmail($emails): array
    {
        $out = [];
        try {
            $emails = $emails->filter()->map(fn ($e) => mb_strtolower(trim((string) $e)))->unique()->values();
            foreach ($emails->chunk(1000) as $chunk) {
                // TODO cek: nama kolom email di tabel HCIS
                foreach (DB::table('api_emp_hcis')->whereIn('email', $chunk->all())->get(['email', 'fullname']) as $r) {
                    $name = trim((string) $r->fullname);
                    if ($name !== '' && $r->email) {
                        $out[mb_strtolower(trim((string) $r->email))] = $name;
                    }
                }
            }
        } catch (\Throwable $e) {
            report($e);
            return [];
        }

        return $out;
    }

    /**
     * Nama lengkap HCIS (kolom fullname) untuk employee_id yang ada di data halaman ini saja,
     * jadi ikut terpotong oleh akses Bisnis unit. Satu query per 1000 ID.
     * Bila tabel atau kolom tidak bisa dibaca, dashboard tetap jalan dengan nama Grab.
     *
     * @return array<string,string> employee_id => fullname
     */
    private static function hcisNames($employeeIds): array
    {
        $out = [];
        try {
            foreach ($employeeIds->filter()->unique()->values()->chunk(1000) as $chunk) {
                // TODO cek: nama tabel (di engine tertulis api_emp_hcis) dan kolom employee_id
                foreach (DB::table('api_emp_hcis')->whereIn('employee_id', $chunk->all())->get(['employee_id', 'fullname']) as $r) {
                    $name = trim((string) $r->fullname);
                    if ($name !== '') {
                        $out[(string) $r->employee_id] = $name;
                    }
                }
            }
        } catch (\Throwable $e) {
            report($e);
            return [];
        }

        return $out;
    }
}
