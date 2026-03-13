<?php

namespace App\Notifications;

use App\Models\Sanction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class SanctionNotification extends Notification
{
    use Queueable;

    protected $sanction;

    /**
     * Create a new notification instance.
     */
    public function __construct(Sanction $sanction)
    {
        $this->sanction = $sanction;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🔴 Nouvelle sanction - ' . $this->sanction->niveau)
            ->greeting('Bonjour ' . $notifiable->prenom . ',')
            ->line('Une nouvelle sanction a été enregistrée à votre encontre.')
            ->line('**Niveau :** ' . $this->formatNiveau($this->sanction->niveau))
            ->line('**Motif :** ' . $this->sanction->motif)
            ->line('**Description :** ' . $this->sanction->description)
            ->line('**Date :** ' . $this->sanction->date_sanction->format('d/m/Y'))
            ->action('Voir les détails', url('/admin/sanctions/' . $this->sanction->id))
            ->line('Si vous avez des questions, veuillez contacter votre coach.')
            ->salutation('L\'équipe de pointage');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'sanction_id' => $this->sanction->id,
            'niveau' => $this->sanction->niveau,
            'motif' => $this->sanction->motif,
            'description' => $this->sanction->description,
            'date' => $this->sanction->date_sanction->format('Y-m-d'),
            'coach_id' => $this->sanction->coach_id,
            'coach_nom' => $this->sanction->coach->nomComplet ?? 'Coach',
            'type' => 'sanction',
            'est_lue' => false,
        ];
    }

    /**
     * Formater le niveau de sanction pour l'affichage
     */
    private function formatNiveau($niveau): string
    {
        return match($niveau) {
            'avertissement' => '⚠️ Avertissement',
            'blame' => '📝 Blâme',
            'suspension' => '🔇 Suspension',
            'exclusion' => '❌ Exclusion',
            default => $niveau,
        };
    }
}