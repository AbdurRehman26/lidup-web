<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActivationKeyIssued extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $plainTextKey,
        public readonly bool $replaced = false,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->replaced ? 'Your new LidUp activation key' : 'Your LidUp activation key')
            ->greeting("Hi {$notifiable->name},")
            ->line($this->replaced
                ? 'Your previous activation key has been replaced. Use this new key the next time you activate LidUp:'
                : 'Your LidUp activation key is ready:')
            ->line($this->plainTextKey)
            ->line('Keep this key private and save it somewhere secure. For security, it cannot be displayed again.')
            ->action('Open your LidUp account', url('/dashboard'))
            ->line('If you did not request this key, contact LidUp support.');
    }
}
