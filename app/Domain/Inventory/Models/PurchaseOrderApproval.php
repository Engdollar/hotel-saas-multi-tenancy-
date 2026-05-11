<?php

namespace App\Domain\Inventory\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderApproval extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $table = 'procurement_purchase_order_approvals';

    protected $fillable = [
        'company_id',
        'purchase_order_id',
        'sequence_number',
        'approver_user_id',
        'status',
        'acted_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'acted_at' => 'datetime',
        ];
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}