<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RepairResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'plate_number'   => $this->vehicle->plate_number ?? null,
            'business_unit'  => $this->vehicle && $this->vehicle->businessUnit ? [
                'id'   => $this->vehicle->businessUnit->id_bisnis_unit,
                'name' => $this->vehicle->businessUnit->nama_bisnis_unit,
            ] : null,
            'report_date'    => optional($this->report_date)->format('Y-m-d'),
            'complaint'      => $this->complaint,
            'diagnosis'      => $this->diagnosis,
            'parts_replaced' => $this->parts_replaced,
            'labor_cost'     => (float) $this->labor_cost,
            'parts_cost'     => (float) $this->parts_cost,
            'status'         => $this->status,
            'notes'          => $this->notes,
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}