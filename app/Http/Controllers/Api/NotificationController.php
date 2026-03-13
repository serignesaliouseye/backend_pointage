<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class NotificationController extends Controller
{
    /**
     * Récupérer toutes les notifications de l'utilisateur
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $notifications = $user->notifications()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'non_lues' => $user->unreadNotifications->count()
            ]
        ]);
    }

    /**
     * Récupérer les notifications non lues
     */
    public function unread(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'data' => $user->unreadNotifications
        ]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue'
        ]);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications ont été marquées comme lues'
        ]);
    }

    /**
     * Supprimer une notification
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        $notification = $user->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification supprimée'
        ]);
    }

    /**
     * Supprimer toutes les notifications
     */
    public function destroyAll(Request $request)
    {
        $user = $request->user();
        $user->notifications()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications ont été supprimées'
        ]);
    }
}