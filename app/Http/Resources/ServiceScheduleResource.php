<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceScheduleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                    => $this->id,
            'plate_number'          => $this->vehicle->plate_number ?? null,
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
        ];
    }
}
