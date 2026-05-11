<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SystemActivityNotification;
use Illuminate\Support\Facades\Notification;

class AdminNotificationService
{
    public function send(string $title, string $message, ?string $url = null): void
    {
        $users = User::withoutGlobalScopes()
            ->whereHas('roles', fn ($query) => $query->withoutGlobalScopes()->where('name', 'Super Admin'))
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new SystemActivityNotification($title, $message, $url));
    }
}