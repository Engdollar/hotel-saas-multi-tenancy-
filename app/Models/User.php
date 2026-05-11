<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\BelongsToCompany;
use App\Support\AssetPath;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use BelongsToCompany, HasFactory, HasRoles, LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'profile_image_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'profile_image_path'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected function profileImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->profile_image_path
                ? AssetPath::storageUrl($this->profile_image_path)
                : asset('images/default-avatar.svg'),
        );
    }

    public function dashboardPreference()
    {
        return $this->hasOne(UserDashboardPreference::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles()->withoutGlobalScopes()->where('name', 'Super Admin')->exists();
    }

    protected function initials(): Attribute
    {
        return Attribute::make(
            get: function () {
                return collect(explode(' ', (string) $this->name))
                    ->filter()
                    ->take(2)
                    ->map(fn (string $segment) => strtoupper(substr($segment, 0, 1)))
                    ->implode('');
            },
        );
    }
}
