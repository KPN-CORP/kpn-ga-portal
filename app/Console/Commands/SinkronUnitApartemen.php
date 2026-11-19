<?php

namespace App\Console\Commands;

use App\Services\Apartemen\UnitService;
use Illuminate\Console\Command;

class SinkronUnitApartemen extends Command
{
    protected $signature = 'apartemen:sinkron-unit';
    protected $description = 'Samakan status unit apartemen (READY/TERISI) dengan penempatan yang benar-benar berjalan hari ini';

    public function handle(): int
    {
        $n = UnitService::sinkronSemua(true);
        $this->info("Sinkronisasi selesai. Unit yang berubah: {$n}");

        return self::SUCCESS;
    }
}
