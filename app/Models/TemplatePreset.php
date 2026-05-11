<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TemplatePreset extends Model
{
    use BelongsToCompany, HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'type',
        'slug',
        'name',
        'description',
        'content',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['company_id', 'type', 'slug', 'name', 'description', 'content'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
