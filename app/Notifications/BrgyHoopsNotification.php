<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base class for BrgyHoops email notifications.
 *
 * Subclasses describe the message with content(); this class renders it with
 * the branded emails.notification templates (HTML + plain text).
 *
 * Notifications are sent synchronously (not queued): a queue worker only runs
 * under `composer run dev`, so queued mail would otherwise sit unsent.
 * Send them through App\Support\SafeNotifier so a mail failure never breaks
 * the action that triggered it.
 */
abstract class BrgyHoopsNotification extends Notification
{
    /**
     * Describe the email.
     *
     * @return array{subject: string, heading: string, lines?: array<int, string>,
     *               details?: array<string, string>, actionText?: string|null,
     *               actionUrl?: string|null, outro?: array<int, string>}
     */
    abstract public function content(object $notifiable): array;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $content = $this->content($notifiable);

        $data = [
            'heading' => $content['heading'],
            'greeting' => 'Hi ' . ($notifiable->name ?? 'there') . ',',
            'lines' => $content['lines'] ?? [],
            'details' => array_filter($content['details'] ?? [], fn ($value) => $value !== null && $value !== ''),
            'actionText' => $content['actionText'] ?? null,
            'actionUrl' => $content['actionUrl'] ?? null,
            'outro' => $content['outro'] ?? [],
        ];

        return (new MailMessage)
            ->subject('BrgyHoops: ' . $content['subject'])
            ->view(['emails.notification', 'emails.notification-text'], $data);
    }
}
