<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FuelLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                   => $this->id,
            'plate_number'         => $this->vehicle->plate_number ?? null,
            'filling_date'         => optional($this->filling_date)->format('Y-m-d'),
            'odometer_start'       => $this->odometer_start,
            'fuel_liters'          => (float) $this->fuel_liters,
            'fuel_price_per_liter' => $this->fuel_price_per_liter !== null ? (float) $this->fuel_price_per_liter : null,
            'total_cost'           => $this->total_cost !== null ? (float) $this->total_cost : null,
            'is_verified'          => (bool) $this->is_verified,
            'verified_at'          => $this->verified_at?->toIso8601String(),
            'notes'                => $this->notes,
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];

        // Sengaja TIDAK di-expose (internal): vehicle_id, driver_id, user_id,
        // verified_by (raw FK id), receipt_file (path storage internal)
    }
}
