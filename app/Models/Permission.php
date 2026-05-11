<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use BelongsToCompany, LogsActivity;

    protected $fillable = [
        'company_id',
        'name',
        'guard_name',
    ];

    public function allowsGlobalCompanyRecords(): bool
    {
        return true;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['company_id', 'name', 'guard_name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}