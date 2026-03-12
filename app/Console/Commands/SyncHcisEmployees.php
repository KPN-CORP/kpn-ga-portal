<?php

namespace App\Console\Commands;

use App\Models\ApiEmpHcis;
use App\Services\HcisEmployeeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncHcisEmployees extends Command
{
    protected $signature = 'hcis:sync-employees';
    protected $description = 'Sinkronisasi data karyawan dari HCIS ke database lokal';

    protected $service;

    public function __construct(HcisEmployeeService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle()
    {
        $this->info('Memulai sinkronisasi data karyawan dari HCIS...');

        try {
            $employees = $this->service->fetchEmployees();

            if (empty($employees)) {
                $this->warn('Tidak ada data karyawan yang diterima dari HCIS.');
                return 0;
            }

            $bar = $this->output->createProgressBar(count($employees));
            $bar->start();

            $updated = 0;
            $created = 0;

            foreach ($employees as $empData) {
                $data = [
                    'employee_id'    => $empData['employee_id'],
                    'fullname'       => $empData['fullname'],
                    'email'          => $empData['email'] ?? null,
                    'group_company'  => $empData['group_company'] ?? null,
                    'office_area'    => $empData['office_area'] ?? null,
                    'manager_l1_id'  => $empData['manager_l1_id'] ?? null,
                    'manager_l2_id'  => $empData['manager_l2_id'] ?? null,
                ];

                $employee = ApiEmpHcis::updateOrCreate(
                    ['employee_id' => $data['employee_id']],
                    $data
                );

                if ($employee->wasRecentlyCreated) {
                    $created++;
                } else {
                    if ($employee->wasChanged()) {
                        $updated++;
                    }
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            $this->info("Sinkronisasi selesai. Created: {$created}, Updated: {$updated}");
            Log::info('HCIS sync completed', compact('created', 'updated'));

            return 0;

        } catch (\Exception $e) {
            $this->error('Gagal sinkronisasi: ' . $e->getMessage());
            Log::error('HCIS sync failed: ' . $e->getMessage());
            return 1;
        }
    }
}