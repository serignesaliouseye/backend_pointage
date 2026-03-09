<?php

namespace App\Services;

use App\Models\QrToken;
use App\Models\User;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;

class QrCodeService
{
    /**
     * Générer un QR code quotidien pour le pointage
     */
    public function genererQrQuotidien(User $user): array
    {
        // Générer un token unique
        $token = Str::random(64);
        
        // Définir la plage horaire (8h00 - 18h00)
        $heureDebut = '08:00:00';
        $heureFin = '18:00:00';
        
        // Vérifier s'il existe déjà un token pour aujourd'hui
        $qrExistant = QrToken::whereDate('date_validite', Carbon::today())
            ->where('cree_par', $user->id)
            ->first();
            
        if ($qrExistant) {
            // Retourner le token existant
            return [
                'token' => $qrExistant,
                'qr_code' => $this->genererImageQr($qrExistant->token),
                'qr_base64' => $this->genererQrBase64($qrExistant->token)
            ];
        }
        
        // Créer un nouveau token
        $qrToken = QrToken::create([
            'token' => $token,
            'date_validite' => Carbon::today(),
            'heure_debut' => $heureDebut,
            'heure_fin' => $heureFin,
            'est_utilise' => false,
            'cree_par' => $user->id
        ]);

        return [
            'token' => $qrToken,
            'qr_code' => $this->genererImageQr($token),
            'qr_base64' => $this->genererQrBase64($token)
        ];
    }

    /**
     * Générer un QR code pour une date spécifique
     */
    public function genererQrPourDate(User $user, string $date, string $heureDebut = '08:00:00', string $heureFin = '18:00:00'): array
    {
        $token = Str::random(64);
        
        $qrToken = QrToken::create([
            'token' => $token,
            'date_validite' => Carbon::parse($date),
            'heure_debut' => $heureDebut,
            'heure_fin' => $heureFin,
            'est_utilise' => false,
            'cree_par' => $user->id
        ]);

        return [
            'token' => $qrToken,
            'qr_code' => $this->genererImageQr($token),
            'qr_base64' => $this->genererQrBase64($token)
        ];
    }

    /**
     * Générer une image QR code (format SVG)
     */
    public function genererImageQr(string $token): string
    {
        $data = [
            'token' => $token,
            'type' => 'pointage',
            'timestamp' => now()->timestamp
        ];

        return QrCode::size(300)
            ->margin(1)
            ->errorCorrection('H')
            ->generate(json_encode($data));
    }

    /**
     * Générer un QR code en base64 (pour affichage dans le navigateur)
     */
    public function genererQrBase64(string $token): string
    {
        $data = [
            'token' => $token,
            'type' => 'pointage',
            'timestamp' => now()->timestamp
        ];

        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->errorCorrection('H')
            ->generate(json_encode($data));

        return 'data:image/png;base64,' . base64_encode($qrCode);
    }

    /**
     * Générer un QR code en format PNG (téléchargeable)
     */
    public function genererQrPng(string $token): \Illuminate\Http\Response
    {
        $data = [
            'token' => $token,
            'type' => 'pointage',
            'timestamp' => now()->timestamp
        ];

        $qrCode = QrCode::format('png')
            ->size(400)
            ->margin(2)
            ->errorCorrection('H')
            ->generate(json_encode($data));

        return response($qrCode)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'attachment; filename="qr-pointage-' . date('Y-m-d') . '.png"');
    }

    /**
     * Valider un token QR
     */
    public function validerToken(string $token): ?QrToken
    {
        return QrToken::where('token', $token)
            ->whereDate('date_validite', Carbon::today())
            ->where('est_utilise', false)
            ->first();
    }

    /**
     * Marquer un token comme utilisé
     */
    public function marquerCommeUtilise(QrToken $qrToken): bool
    {
        return $qrToken->update(['est_utilise' => true]);
    }

    /**
     * Obtenir le QR code actif du jour
     */
    public function getQrActifDuJour(User $user): ?QrToken
    {
        return QrToken::where('cree_par', $user->id)
            ->whereDate('date_validite', Carbon::today())
            ->first();
    }

    /**
     * Générer plusieurs QR codes pour une période
     */
    public function genererQrPourPeriode(User $user, string $dateDebut, string $dateFin, string $heureDebut = '08:00:00', string $heureFin = '18:00:00'): array
    {
        $debut = Carbon::parse($dateDebut);
        $fin = Carbon::parse($dateFin);
        $qrs = [];

        for ($date = $debut->copy(); $date <= $fin; $date->addDay()) {
            // Vérifier si un QR existe déjà pour cette date
            $existant = QrToken::whereDate('date_validite', $date)
                ->where('cree_par', $user->id)
                ->first();

            if (!$existant) {
                $token = Str::random(64);
                $qrToken = QrToken::create([
                    'token' => $token,
                    'date_validite' => $date->copy(),
                    'heure_debut' => $heureDebut,
                    'heure_fin' => $heureFin,
                    'est_utilise' => false,
                    'cree_par' => $user->id
                ]);
                
                $qrs[] = [
                    'date' => $date->format('Y-m-d'),
                    'token' => $qrToken,
                    'qr_base64' => $this->genererQrBase64($token)
                ];
            }
        }

        return $qrs;
    }

    /**
     * Nettoyer les anciens tokens (à exécuter périodiquement)
     */
    public function nettoyerAnciensTokens(): int
    {
        // Supprimer les tokens de plus de 30 jours
        return QrToken::where('date_validite', '<', Carbon::now()->subDays(30))
            ->delete();
    }
}