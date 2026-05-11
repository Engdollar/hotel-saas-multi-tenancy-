<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ledger_account_id' => $this->ledger_account_id,
            'name' => $this->name,
            'bank_name' => $this->bank_name,
            'account_number_last4' => $this->account_number_last4,
            'currency_code' => $this->currency_code,
            'current_balance' => $this->current_balance,
            'is_active' => $this->is_active,
            'opened_at' => $this->opened_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}