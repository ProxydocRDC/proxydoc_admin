<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StatusChangedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }
 public function via($notifiable)
    {
        return ['s.masimango@proxydoc.org']; // ou ['database', 'sms'] selon ce que tu veux
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("Votre statut a changé")
            ->line("Bonjour {$this->user->firstname},")
            ->line("Votre nouveau statut est : " . $this->getStatusName($this->user->status));
    }

    private function getStatusName($code)
    {
        return match ($code) {
            1 => 'Activé',
            2 => 'En attente',
            3 => 'Désactivé',
            4 => 'Supprimé',
            default => 'Inconnu',
        };
    }
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
