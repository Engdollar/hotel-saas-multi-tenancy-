<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDashboardPreference extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'widgets',
        'layout',
        'drag_enabled',
    ];

    protected function casts(): array
    {
        return [
            'widgets' => 'array',
            'layout' => 'array',
            'drag_enabled' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}