<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use BelongsToCompany, LogsActivity;

    protected $fillable = [
        'company_id',
        'name',
        'guard_name',
        'is_locked',
    ];

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
        ];
    }

    public function allowsGlobalCompanyRecords(): bool
    {
        return true;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['company_id', 'name', 'guard_name', 'is_locked'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}