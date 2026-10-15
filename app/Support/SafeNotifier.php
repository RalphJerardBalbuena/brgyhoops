<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Throwable;

/**
 * Sends notifications without letting delivery problems break the request.
 *
 * - Skips missing recipients and users without a valid email address.
 * - Removes duplicate recipients (e.g. one manager owning both teams).
 * - Catches and reports mail failures (missing API key, provider outage).
 */
class SafeNotifier
{
    /**
     * @param  User|iterable<User|null>|null  $recipients
     */
    public static function send(User|iterable|null $recipients, Notification $notification): void
    {
        $recipients = collect($recipients instanceof User ? [$recipients] : ($recipients ?? []))
            ->filter(fn ($user) => $user instanceof User
                && filter_var($user->email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique(fn (User $user) => $user->getKey())
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        try {
            NotificationFacade::send($recipients, $notification);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
