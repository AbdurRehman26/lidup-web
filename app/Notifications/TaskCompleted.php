<?php

namespace App\Notifications;

use App\Models\TaskCompletionEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCompleted extends Notification
{
    use Queueable;

    public function __construct(public readonly TaskCompletionEvent $event) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = ucfirst($this->event->status);
        $mail = (new MailMessage)
            ->subject("Your LidUp task {$this->event->status}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your task has finished with status: {$status}.")
            ->line($this->event->summary);

        if ($this->event->duration_seconds !== null) {
            $mail->line('Total time: '.$this->formatDuration($this->event->duration_seconds));
        }

        if ($this->event->device_name) {
            $mail->line("Mac: {$this->event->device_name}");
        }

        return $mail
            ->action('Open your LidUp account', url('/dashboard'))
            ->line('Lidup your Mac.');
    }

    private function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        return collect([
            $hours > 0 ? "{$hours}h" : null,
            $minutes > 0 ? "{$minutes}m" : null,
            $remainingSeconds > 0 || ($hours === 0 && $minutes === 0) ? "{$remainingSeconds}s" : null,
        ])->filter()->implode(' ');
    }
}
