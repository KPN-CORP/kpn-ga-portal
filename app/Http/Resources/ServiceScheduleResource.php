<?php

namespace App\Http\Resources;

use App\Support\OwnerLookupCache;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceScheduleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                    => $this->id,
            'plate_number'          => $this->vehicle->plate_number ?? null,
            'business_unit'         => $this->vehicle && $this->vehicle->businessUnit ? [
                'id'   => $this->vehicle->businessUnit->id_bisnis_unit,
                'name' => $this->vehicle->businessUnit->nama_bisnis_unit,
            ] : null,
            'service_date'          => optional($this->service_date)->format('Y-m-d'),
            'odometer_at_service'   => $this->odometer_at_service,
            'service_type'          => $this->service_type,
            'workshop_name'         => $this->workshop_name,
            'cost'                  => (float) $this->cost,
            'next_service_odometer' => $this->next_service_odometer,
            'next_service_date'     => optional($this->next_service_date)->format('Y-m-d'),
            'notes'                 => $this->notes,
            'created_at'            => $this->created_at?->toIso8601String(),
            'updated_at'            => $this->updated_at?->toIso8601String(),

            // BARU (v3.0) — sinkron sesuai plat nomor ke db_asset_vehicles (AMS)
            'owner'                 => OwnerLookupCache::get($this->vehicle->plate_number ?? null),
        ];
    }
}
