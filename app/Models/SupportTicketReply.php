<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicketReply extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'support_ticket_id',
        'company_id',
        'user_id',
        'is_internal',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
        ];
    }

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
