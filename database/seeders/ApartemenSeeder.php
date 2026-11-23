<?php
// database/seeders/ApartemenSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Apartemen\Apartemen;
use App\Models\Apartemen\ApartemenUnit;
use App\Models\Apartemen\ApartemenAset;
use App\Models\Apartemen\ApartemenPeraturan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ApartemenSeeder extends Seeder
{
    public function run()
    {
        // 1. Create Apartemen
        $apartemen1 = Apartemen::create([
            'nama_apartemen' => 'Apartemen Green Lake City',
            'alamat' => 'Jl. Green Lake City No. 1, Jakarta Barat',
            'penanggung_jawab' => 'Budi Santoso',
            'kontak_darurat' => '081234567890',
        ]);

        $apartemen2 = Apartemen::create([
            'nama_apartemen' => 'Apartemen Sudirman Park',
            'alamat' => 'Jl. Sudirman No. 45, Jakarta Pusat',
            'penanggung_jawab' => 'Siti Rahma',
            'kontak_darurat' => '081298765432',
        ]);

        // 2. Create Units
        $units = [
            // Apartemen 1
            ['apartemen_id' => $apartemen1->id, 'nomor_unit' => 'A101', 'kapasitas' => 2, 'status' => 'READY'],
            ['apartemen_id' => $apartemen1->id, 'nomor_unit' => 'A102', 'kapasitas' => 4, 'status' => 'READY'],
            ['apartemen_id' => $apartemen1->id, 'nomor_unit' => 'A103', 'kapasitas' => 2, 'status' => 'TERISI'],
            ['apartemen_id' => $apartemen1->id, 'nomor_unit' => 'A104', 'kapasitas' => 3, 'status' => 'MAINTENANCE'],
            
            // Apartemen 2
            ['apartemen_id' => $apartemen2->id, 'nomor_unit' => 'B201', 'kapasitas' => 2, 'status' => 'READY'],
            ['apartemen_id' => $apartemen2->id, 'nomor_unit' => 'B202', 'kapasitas' => 4, 'status' => 'TERISI'],
            ['apartemen_id' => $apartemen2->id, 'nomor_unit' => 'B203', 'kapasitas' => 3, 'status' => 'READY'],
        ];

        foreach ($units as $unit) {
            ApartemenUnit::create($unit);
        }

        // 3. Create Aset
        $asets = ['AC', 'TV', 'Kulkas', 'Kasur', 'Lemari', 'Meja', 'Kursi', 'Kompor'];
        foreach ($asets as $aset) {
            ApartemenAset::create(['nama_aset' => $aset]);
        }

        // 4. Create Peraturan
        $peraturan = [
            ['apartemen_id' => $apartemen1->id, 'isi_peraturan' => 'Dilarang merokok di dalam ruangan', 'aktif' => 1],
            ['apartemen_id' => $apartemen1->id, 'isi_peraturan' => 'Jam malam pukul 22:00 - 06:00', 'aktif' => 1],
            ['apartemen_id' => $apartemen2->id, 'isi_peraturan' => 'Tamu maksimal 2 orang dan hanya boleh menginap 1 malam', 'aktif' => 1],
        ];

        foreach ($peraturan as $data) {
            ApartemenPeraturan::create($data);
        }

        // 5. Create Test User
        User::firstOrCreate(
            ['email' => 'admin@apartemen.com'],
            [
                'name' => 'Admin Apartemen',
                'password' => Hash::make('password'),
                'role' => 'apt_admin',
            ]
        );
    }
}