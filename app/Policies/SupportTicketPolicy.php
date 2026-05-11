<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;
use App\Policies\Concerns\EnforcesCompanyBoundary;

class SupportTicketPolicy
{
    use EnforcesCompanyBoundary;

    public function viewAny(User $user): bool
    {
        return $user->can('read-ticket');
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        if (! $user->can('show-ticket') && ! $user->can('read-ticket')) {
            return false;
        }

        return $this->inSameCompany($user, $ticket);
    }

    public function create(User $user): bool
    {
        return $user->can('create-ticket');
    }

    public function update(User $user, SupportTicket $ticket): bool
    {
        return ($user->can('update-ticket') || $user->can('edit-ticket'))
            && $this->inSameCompany($user, $ticket);
    }

    public function delete(User $user, SupportTicket $ticket): bool
    {
        return $user->can('delete-ticket') && $this->inSameCompany($user, $ticket);
    }
}
