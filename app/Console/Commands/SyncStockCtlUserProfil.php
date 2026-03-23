<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\BisnisUnit;
use App\Models\StockCtl\AreaKerja;
use App\Models\StockCtl\UserProfil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncStockCtlUserProfil extends Command
{
    protected $signature = 'sync:stock-ctl-user-profil {--create-area : Buat area baru jika belum ada}';
    protected $description = 'Sinkronisasi data stock_ctl_user_profil dari api_emp_hcis berdasarkan username';

    public function handle()
    {
        $this->info('Memulai sinkronisasi data profil user ATK...');

        $users = User::all();
        $count = 0;
        $errors = 0;

        foreach ($users as $user) {
            // Cari data karyawan di api_emp_hcis berdasarkan username (employee_id)
            $karyawan = DB::table('api_emp_hcis')->where('employee_id', $user->username)->first();

            if (!$karyawan) {
                continue;
            }

            // 1. Tentukan Bisnis Unit
            $idBisnisUnit = null;
            if (!empty($karyawan->group_company)) {
                $bisnisUnit = BisnisUnit::where('nama_bisnis_unit', $karyawan->group_company)->first();
                if ($bisnisUnit) {
                    $idBisnisUnit = $bisnisUnit->id_bisnis_unit;
                } else {
                    $this->warn("Bisnis unit '{$karyawan->group_company}' tidak ditemukan di tb_bisnis_unit untuk user {$user->username}");
                }
            }

            // 2. Tentukan Area Kerja
            $idAreaKerja = null;
            if ($idBisnisUnit && !empty($karyawan->office_area)) {
                $area = AreaKerja::where('nama_area', $karyawan->office_area)
                                 ->where('id_bisnis_unit', $idBisnisUnit)
                                 ->first();
                if ($area) {
                    $idAreaKerja = $area->id_area_kerja;
                } elseif ($this->option('create-area')) {
                    $areaBaru = AreaKerja::create([
                        'nama_area' => $karyawan->office_area,
                        'id_bisnis_unit' => $idBisnisUnit,
                    ]);
                    $idAreaKerja = $areaBaru->id_area_kerja;
                    $this->info("Area kerja baru dibuat: {$karyawan->office_area} untuk unit {$karyawan->group_company}");
                } else {
                    $this->warn("Area '{$karyawan->office_area}' untuk unit {$karyawan->group_company} tidak ditemukan. Gunakan --create-area untuk membuat otomatis.");
                }
            }

            // 3. Tentukan Atasan (manager_l1_id)
            $idApprover = null;
            if (!empty($karyawan->manager_l1_id)) {
                $approver = User::where('username', $karyawan->manager_l1_id)->first();
                if ($approver) {
                    $idApprover = $approver->id;
                } else {
                    $this->warn("Atasan L1 dengan username '{$karyawan->manager_l1_id}' tidak ditemukan di tabel users.");
                }
            }

            // 4. Siapkan data untuk disimpan (termasuk unit)
            $dataUpdate = [
                'id_bisnis_unit' => $idBisnisUnit,
                'id_area_kerja'  => $idAreaKerja,
                'unit'           => $karyawan->unit ?? null, // <-- tambahkan unit
                'id_approver'    => $idApprover,
            ];

            // 5. Simpan ke stock_ctl_user_profil
            try {
                UserProfil::updateOrCreate(
                    ['id_user' => $user->id],
                    $dataUpdate
                );
                $count++;
            } catch (\Exception $e) {
                $this->error("Gagal menyimpan profil untuk user {$user->id}: " . $e->getMessage());
                Log::error("SyncStockCtlUserProfil error: " . $e->getMessage(), ['user_id' => $user->id]);
                $errors++;
            }
        }

        $this->info("Sinkronisasi selesai. {$count} data diproses, {$errors} error.");
        return Command::SUCCESS;
    }
}