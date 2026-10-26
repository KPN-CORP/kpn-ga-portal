<?php

namespace App\Services\FinGrab;

use App\Models\FinGrabm\FinGrabTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Menyusun data untuk halaman (struktur X yang dipakai dashboard).
 * Pemotongan akses per Bisnis unit terjadi di sini, di query, sebelum data keluar dari server.
 */
class FinGrabPayload
{
    private const VERTICAL = ['food' => 0, 'transport' => 1, 'express' => 2];

    public static function build(array $scope): array
    {
        $q = FinGrabTransaction::query()->orderBy('trx_date')->orderBy('id');
        if (!$scope['all']) {
            $q->whereIn('id_bisnis_unit', $scope['ids']); // PT belum terpetakan (null) hanya terlihat oleh Corporate
        }
        $all = $q->get();

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

            $rows[] = [
                $t->trx_date->format('Y-m-d'),
                $coIx[$t->pt_name] ?? 0,
                $grIx[$t->group_name] ?? 0,
                $emIx[$key],
                self::VERTICAL[$t->vertical] ?? 0,
                (int) $t->amount,
                (int) $t->trx_hour,
                $ciIx[$t->city ?: '-'] ?? 0,
                $t->booking_id,
                [
                    $t->pickup ?? '', $t->dropoff ?? '', $t->intermediate ?? '', (float) $t->distance_km,
                    $t->trip_description ?? '', $t->taxi_type ?? '',
                    $t->base_fare, $t->promo, $t->toll, $t->late_fee, $t->other_fee,
                    $t->trip_code ?? '',
                ],
                $t->id_bisnis_unit ? ($buNames[$t->id_bisnis_unit] ?? 'Belum terpetakan') : 'Belum terpetakan',
            ];
        }

        return ['co' => $co, 'gr' => $gr, 'ci' => $ci, 'em' => $em, 'rows' => $rows];
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