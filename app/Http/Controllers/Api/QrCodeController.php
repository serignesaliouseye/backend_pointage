<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class QrCodeController extends Controller
{
    protected $qrCodeService;

    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Générer un nouveau QR code (admin/coach)
     */
    public function generer(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
            'heure_debut' => 'nullable|date_format:H:i:s',
            'heure_fin' => 'nullable|date_format:H:i:s',
        ]);

        $user = $request->user();
        
        // Vérifier les permissions
        if (!in_array($user->role, ['admin', 'coach'])) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        if ($request->has('date')) {
            $qr = $this->qrCodeService->genererQrPourDate(
                $user,
                $request->date,
                $request->heure_debut ?? '08:00:00',
                $request->heure_fin ?? '18:00:00'
            );
        } else {
            $qr = $this->qrCodeService->genererQrQuotidien($user);
        }

        return response()->json([
            'success' => true,
            'message' => 'QR code généré avec succès',
            'data' => [
                'token' => $qr['token']->token,
                'date_validite' => $qr['token']->date_validite,
                'heure_debut' => $qr['token']->heure_debut,
                'heure_fin' => $qr['token']->heure_fin,
                'qr_code' => $qr['qr_base64'] // En base64 pour affichage
            ]
        ]);
    }

    /**
     * Récupérer le QR code actuel du jour
     */
    public function qrActuel(Request $request)
    {
        $user = $request->user();
        
        if (!in_array($user->role, ['admin', 'coach'])) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $qrActif = $this->qrCodeService->getQrActifDuJour($user);

        if (!$qrActif) {
            // Générer automatiquement si aucun QR n'existe
            $qr = $this->qrCodeService->genererQrQuotidien($user);
            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $qr['token']->token,
                    'date_validite' => $qr['token']->date_validite,
                    'heure_debut' => $qr['token']->heure_debut,
                    'heure_fin' => $qr['token']->heure_fin,
                    'qr_code' => $qr['qr_base64']
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $qrActif->token,
                'date_validite' => $qrActif->date_validite,
                'heure_debut' => $qrActif->heure_debut,
                'heure_fin' => $qrActif->heure_fin,
                'qr_code' => $this->qrCodeService->genererQrBase64($qrActif->token)
            ]
        ]);
    }

    /**
     * Télécharger le QR code en PNG
     */
    public function telecharger(Request $request, $token)
    {
        $user = $request->user();
        
        if (!in_array($user->role, ['admin', 'coach'])) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        return $this->qrCodeService->genererQrPng($token);
    }

    /**
     * Valider un QR code (pour le scan)
     */
    public function valider(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $qrToken = $this->qrCodeService->validerToken($request->token);

        if (!$qrToken) {
            return response()->json([
                'success' => false,
                'message' => 'QR code invalide ou expiré'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'QR code valide',
            'data' => [
                'token_id' => $qrToken->id,
                'date_validite' => $qrToken->date_validite,
                'heure_debut' => $qrToken->heure_debut,
                'heure_fin' => $qrToken->heure_fin
            ]
        ]);
    }
}