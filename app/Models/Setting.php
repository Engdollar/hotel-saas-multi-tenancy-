<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Setting extends Model
{
    use BelongsToCompany, HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'key',
        'value',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['company_id', 'key', 'value'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}