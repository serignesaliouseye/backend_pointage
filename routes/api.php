<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PointageController;
use App\Http\Controllers\Api\CoachController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\QrCodeController;
use App\Http\Controllers\Api\NotificationController; // ← AJOUT IMPORTANT
use Illuminate\Support\Facades\Route;

// Routes publiques
Route::post('/login', [AuthController::class, 'login']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Pointages (pour tous les utilisateurs connectés)
    Route::prefix('pointages')->group(function () {
        Route::post('/scanner', [PointageController::class, 'scannerQr']);
        Route::get('/historique', [PointageController::class, 'historique']);
        Route::get('/stats', [PointageController::class, 'stats']);
    });
    
    // Routes pour les coaches
    Route::middleware('role:coach')->prefix('coach')->group(function () {
        Route::get('/stagiaires', [CoachController::class, 'mesStagiaires']);
        Route::get('/stagiaires/{id}', [CoachController::class, 'detailStagiaire']);
        Route::put('/pointages/{id}/corriger', [CoachController::class, 'corrigerPointage']);
        Route::post('/sanctions', [CoachController::class, 'sanctionner']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () { // ← Supprimé le doublon middleware
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::delete('/all', [NotificationController::class, 'destroyAll']);
    });
    
    // Routes pour les admins
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::apiResource('users', AdminController::class);
        Route::post('/users/{user}/affecter-coach', [AdminController::class, 'affecterCoach']);
        Route::get('/dashboard/stats', [AdminController::class, 'dashboardStats']);
        Route::get('/pointages/export', [AdminController::class, 'exportPointages']);
        
        // Gestion des QR codes
        Route::post('/qr-codes/generer', [QrCodeController::class, 'generer']);
        Route::get('/qr-codes/actuel', [QrCodeController::class, 'qrActuel']);
    });
});