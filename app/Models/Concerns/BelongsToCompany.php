<?php

namespace App\Models\Concerns;

use App\Support\Tenancy\CurrentCompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            $context = app(CurrentCompanyContext::class);

            if ($context->bypassesTenancy()) {
                return;
            }

            $companyId = $context->id();

            if ($companyId === null) {
                return;
            }

            $model = $builder->getModel();
            $companyColumn = $model->getTable().'.company_id';

            if (method_exists($model, 'allowsGlobalCompanyRecords') && $model->allowsGlobalCompanyRecords()) {
                $builder->where(function (Builder $query) use ($companyColumn, $companyId) {
                    $query->where($companyColumn, $companyId)
                        ->orWhereNull($companyColumn);
                });

                return;
            }

            $builder->where($companyColumn, $companyId);
        });

        static::creating(function ($model) {
            $context = app(CurrentCompanyContext::class);

            if ($context->bypassesTenancy()) {
                return;
            }

            if (! isset($model->company_id) && $context->id() !== null) {
                $model->company_id = $context->id();
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        if (isset($this->company_id) && $this->company_id !== null) {
            $activity->company_id = $this->company_id;

            return;
        }

        $context = app(CurrentCompanyContext::class);

        if (! $context->bypassesTenancy() && $context->id() !== null) {
            $activity->company_id = $context->id();
        }
    }
}
