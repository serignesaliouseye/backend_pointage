<?php

namespace App\Traits;

use App\Notifications\SanctionNotification;
use App\Models\Sanction;

trait HasNotifications
{
    /**
     * Envoyer une notification de sanction
     */
    public function notifySanction(Sanction $sanction)
    {
        $this->notify(new SanctionNotification($sanction));
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllNotificationsAsRead()
    {
        return $this->unreadNotifications->markAsRead();
    }

    /**
     * Supprimer toutes les notifications
     */
    public function deleteAllNotifications()
    {
        return $this->notifications()->delete();
    }
}