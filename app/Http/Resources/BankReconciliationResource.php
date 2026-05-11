<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankReconciliationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bank_account_id' => $this->bank_account_id,
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'statement_ending_balance' => $this->statement_ending_balance,
            'book_ending_balance' => $this->book_ending_balance,
            'cleared_balance' => $this->cleared_balance,
            'status' => $this->status,
            'completed_at' => $this->completed_at,
            'notes' => $this->notes,
            'lines' => BankReconciliationLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}