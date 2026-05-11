<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    use BelongsToCompany;
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_WAITING_ON_CUSTOMER = 'waiting_on_customer';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    protected $fillable = [
        'company_id',
        'created_by_user_id',
        'assigned_to_user_id',
        'ticket_number',
        'subject',
        'category',
        'priority',
        'status',
        'first_response_at',
        'resolved_at',
        'last_reply_at',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'last_reply_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $ticket): void {
            if (! $ticket->ticket_number) {
                $ticket->ticket_number = 'TKT-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
            }
        });
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_OPEN,
            self::STATUS_IN_PROGRESS,
            self::STATUS_WAITING_ON_CUSTOMER,
            self::STATUS_RESOLVED,
            self::STATUS_CLOSED,
        ];
    }

    public static function priorities(): array
    {
        return [
            self::PRIORITY_LOW,
            self::PRIORITY_MEDIUM,
            self::PRIORITY_HIGH,
            self::PRIORITY_URGENT,
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function replies()
    {
        return $this->hasMany(SupportTicketReply::class)->latest();
    }
}
