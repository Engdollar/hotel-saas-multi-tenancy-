<?php

namespace App\Domain\Billing\Models;

use App\Models\Company;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantSubscription extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const STATUS_TRIAL = 'trial';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'saas_tenant_subscriptions';

    protected $fillable = [
        'company_id',
        'subscription_plan_id',
        'status',
        'billing_cycle',
        'starts_at',
        'ends_at',
        'renews_at',
        'trial_ends_at',
        'suspended_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'renews_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }
}