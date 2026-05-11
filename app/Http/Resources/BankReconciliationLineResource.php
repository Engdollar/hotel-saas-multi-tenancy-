<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankReconciliationLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entry_type' => $this->entry_type,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'description' => $this->description,
            'transaction_date' => $this->transaction_date,
            'amount' => $this->amount,
            'is_cleared' => $this->is_cleared,
            'cleared_at' => $this->cleared_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}