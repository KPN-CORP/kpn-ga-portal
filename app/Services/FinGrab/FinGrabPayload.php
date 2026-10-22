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
}
