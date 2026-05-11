<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'preferred_supplier_id' => $this->preferred_supplier_id,
            'sku' => $this->sku,
            'name' => $this->name,
            'category' => $this->category,
            'unit_of_measure' => $this->unit_of_measure,
            'current_quantity' => $this->current_quantity,
            'reorder_level' => $this->reorder_level,
            'par_level' => $this->par_level,
            'unit_cost' => $this->unit_cost,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'movements' => $this->whenLoaded('movements', fn () => $this->movements->map(fn ($movement) => [
                'id' => $movement->id,
                'movement_type' => $movement->movement_type,
                'quantity_change' => $movement->quantity_change,
                'unit_cost' => $movement->unit_cost,
                'moved_at' => $movement->moved_at,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}