<?php

namespace App\Services\FinGrab;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Perbaikan manual ID karyawan untuk transaksi yang belum cocok dengan HCIS (halaman Kecocokan data).
 *
 * Satu "karyawan" di halaman itu dikenali dengan prioritas yang sama seperti di FinGrabPayload:
 *   ID  -> kind 'id'    (employee_id terisi)
 *   email -> kind 'email' (ID kosong, email terisi)
 *   nama  -> kind 'name'  (ID dan email kosong)
 * Semua transaksi dengan identitas itu dipasangkan ke ID karyawan HCIS yang dipilih, dan koreksinya disimpan
 * di fingrab_id_overrides supaya upload BCR berikutnya memakai koreksi yang sama.
 */
class FinGrabIdFix
{
    public static function apply(string $kind, string $value, string $rawId, ?int $userId, bool $dry = false): array
    {
        $id = preg_replace('/\s+/', '', $rawId);
        if (preg_match('/^\d{10}$/', $id)) {
            $id = '0' . $id; // sama seperti importer: 10 digit diberi awalan 0
        }
        if (!preg_match('/^\d{11,12}$/', $id)) {
            return ['error' => 'ID karyawan harus 11 atau 12 digit angka (10 digit otomatis diberi awalan 0).'];
        }

        $emp = DB::table('api_emp_hcis')->where('employee_id', $id)
            ->first(['employee_id', 'fullname', 'email', 'group_company', 'office_area', 'unit']);
        if (!$emp) {
            return ['error' => "ID {$id} tidak ditemukan di api_emp_hcis."];
        }

        $value = trim($value);
        if ($kind !== 'id') {
            $value = Str::lower($value);
        }
        if ($value === '') {
            return ['error' => 'Baris ini tidak punya ID, email, maupun nama untuk dicocokkan.'];
        }

        $count = self::target($kind, $value)->count();
        if ($count === 0) {
            return ['error' => 'Tidak ada transaksi yang cocok dengan baris ini (mungkin sudah diperbaiki). Muat ulang halaman.'];
        }

        $info = [
            'employee_id' => $emp->employee_id,
            'fullname' => $emp->fullname,
            'group_company' => $emp->group_company,
            'unit' => $emp->unit,
            'office_area' => $emp->office_area,
            'count' => $count,
            'dry' => $dry,
        ];
        if ($dry) {
            return $info;
        }

        $now = now();
        $bu = self::buForGroupCompany($emp->group_company);

        DB::transaction(function () use ($kind, $value, $emp, $bu, $userId, $now) {
            $set = [
                'employee_id' => $emp->employee_id,
                'employee_id_status' => 'manual',
                'employee_id_hint' => '',
                'hcis_match' => 'id',
                'hcis_group_company' => $emp->group_company,
                'hcis_unit' => $emp->unit,
                'hcis_office_area' => $emp->office_area,
                'updated_at' => $now,
            ];
            if ($bu !== null) {
                $set['id_bisnis_unit'] = $bu; // bila group company tidak terpetakan, BU lama (dari PT penagih) dibiarkan
            }
            self::target($kind, $value)->update($set);

            $key = ['match_type' => $kind, 'match_value' => $value];
            $row = DB::table('fingrab_id_overrides')->where($key);
            if ($row->exists()) {
                $row->update(['employee_id' => $emp->employee_id, 'created_by' => $userId, 'updated_at' => $now]);
            } else {
                DB::table('fingrab_id_overrides')->insert($key + [
                    'employee_id' => $emp->employee_id, 'created_by' => $userId,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        });

        return $info;
    }

    private static function target(string $kind, string $value)
    {
        $q = DB::table('fingrab_transactions');

        return match ($kind) {
            'id' => $q->where('employee_id', $value),
            'email' => $q->whereNull('employee_id')->where('employee_email', $value),
            default => $q->whereNull('employee_id')->whereNull('employee_email')->where('employee_name', $value),
        };
    }

    /** Sama dengan BcrImporter: nama group company = nama Bisnis unit, pengecualian di config('fingrab.group_company_map'). */
    private static function buForGroupCompany(?string $gc): ?int
    {
        $key = Str::lower(trim((string) $gc));
        if ($key === '') {
            return null;
        }
        $bus = DB::table('tb_bisnis_unit')->pluck('id_bisnis_unit', 'nama_bisnis_unit')->all();
        $map = [];
        foreach ($bus as $name => $id) {
            $map[Str::lower(trim((string) $name))] = (int) $id;
        }
        foreach ((array) config('fingrab.group_company_map', []) as $g => $buName) {
            if (isset($bus[$buName])) {
                $map[Str::lower(trim((string) $g))] = (int) $bus[$buName];
            }
        }

        return $map[$key] ?? null;
    }
}