<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinGrabPtBisnisUnitSeeder extends Seeder
{
    public function run(): void
    {
        // kata kunci (huruf kecil) di nama PT penagih => nama di tb_bisnis_unit
        $rules = [
            'th indo'          => 'Plantations',
            'cemindo'       => 'Cement',
            'cempaka'       => 'Property',
            'energi unggul' => 'Downstream',
        ];

        foreach ($rules as $keyword => $buName) {
            $id = DB::table('tb_bisnis_unit')->where('nama_bisnis_unit', $buName)->value('id_bisnis_unit');
            if ($id === null) {
                $this->command?->warn("Bisnis unit '{$buName}' tidak ada di tb_bisnis_unit, dilewati.");
                continue;
            }
            DB::table('fingrab_pt_bisnis_unit')->updateOrInsert(
                ['keyword' => $keyword],
                ['id_bisnis_unit' => $id, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}