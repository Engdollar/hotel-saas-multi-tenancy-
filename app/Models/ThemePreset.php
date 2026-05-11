<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ThemePreset extends Model
{
    use BelongsToCompany, HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'slug',
        'name',
        'description',
        'keywords',
        'swatches',
        'light_tokens',
        'dark_tokens',
        'is_generated',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'swatches' => 'array',
            'light_tokens' => 'array',
            'dark_tokens' => 'array',
            'is_generated' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['company_id', 'slug', 'name', 'description', 'keywords', 'swatches', 'light_tokens', 'dark_tokens', 'is_generated'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}