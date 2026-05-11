<?php

namespace App\Domain\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $table = 'saas_subscription_plans';

    protected $fillable = [
        'code',
        'name',
        'monthly_price',
        'yearly_price',
        'currency_code',
        'max_properties',
        'max_users',
        'max_storage_gb',
        'features',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'yearly_price' => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }
}