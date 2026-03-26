<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PointageController;
use App\Http\Controllers\Api\CoachController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\QrCodeController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

// Routes publiques
Route::post('/login', [AuthController::class, 'login']);

/// Routes protégées
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Pointages
    Route::prefix('pointages')->group(function () {
        Route::post('/scanner', [PointageController::class, 'scannerQr']);
        Route::get('/historique', [PointageController::class, 'historique']);
        Route::get('/stats', [PointageController::class, 'stats']);
    });

    // QR codes
    Route::prefix('qr-codes')->group(function () {
        Route::get('/actuel', [QrCodeController::class, 'qrActuel']);
        Route::post('/generer', [QrCodeController::class, 'generer']);
        Route::post('/valider', [QrCodeController::class, 'valider']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::delete('/all', [NotificationController::class, 'destroyAll']);
    });

    // ✅ Routes pour les stagiaires (leurs propres sanctions)
    Route::get('/sanctions', [CoachController::class, 'mesSanctionsStagiaire']);
    Route::post('/sanctions/{id}/read', [CoachController::class, 'marquerSanctionCommeLue']); // ← NOUVEAU

    // Routes pour les coaches
    Route::middleware('role:coach')->prefix('coach')->group(function () {
        Route::get('/stagiaires', [CoachController::class, 'mesStagiaires']);
        Route::get('/stagiaires/{id}', [CoachController::class, 'detailStagiaire']);
        Route::get('/stagiaires/{id}/sanctions', [CoachController::class, 'sanctionsDuStagiaire']);
        Route::get('/pointages', [CoachController::class, 'mesPointages']);
        Route::get('/pointages/date/{date}', [CoachController::class, 'pointagesParDate']);
        Route::get('/pointages/{id}', [CoachController::class, 'detailPointage']);
        Route::put('/pointages/{id}/corriger', [CoachController::class, 'corrigerPointage']);
        Route::post('/pointages/{id}/justificatif', [CoachController::class, 'ajouterJustificatif']);
        Route::get('/sanctions', [CoachController::class, 'mesSanctions']);
        Route::post('/sanctions', [CoachController::class, 'sanctionner']);
        Route::put('/sanctions/{id}', [CoachController::class, 'modifierSanction']);
        Route::delete('/sanctions/{id}', [CoachController::class, 'supprimerSanction']);
    });

    // Routes pour les admins
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::apiResource('users', AdminController::class);
        Route::post('/users/{user}/affecter-coach', [AdminController::class, 'affecterCoach']);
        Route::delete('/users/{user}/retirer-coach', [AdminController::class, 'retirerCoach']);
        Route::post('/users/{id}/toggle-actif', [AdminController::class, 'toggleActif']);
        Route::get('/dashboard/stats', [AdminController::class, 'dashboardStats']);
        Route::get('/pointages/export', [AdminController::class, 'exportPointages']);
        Route::get('/coachs-stagiaires', [AdminController::class, 'coachsWithStagiaires']);
    });
});