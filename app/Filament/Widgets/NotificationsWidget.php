<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class NotificationsWidget extends Widget
{
    // ✅ Sans static en Filament v4
    protected string $view = 'filament.widgets.notifications-widget';

    protected static ?int $sort = 0;

    public function getNotifications()
    {
        return Auth::user()->notifications()->limit(5)->get();
    }

    public function getUnreadCount()
    {
        return Auth::user()->unreadNotifications->count();
    }
}