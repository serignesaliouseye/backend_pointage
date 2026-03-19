<?php

namespace App\Services;

use App\Models\QrToken;
use App\Models\User;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;

class QrCodeService
{
    public function genererQrQuotidien(User $user): array
    {
        $token = Str::random(64);
        $heureDebut = '08:00:00';
        $heureFin = '18:00:00';

        // ✅ cree_par et est_utilise
        $qrExistant = QrToken::whereDate('date_validite', Carbon::today())
            ->where('cree_par', $user->id)
            ->first();

        if ($qrExistant) {
            return [
                'token' => $qrExistant,
                'qr_base64' => $this->genererQrBase64($qrExistant->token)
            ];
        }

        $qrToken = QrToken::create([
            'token' => $token,
            'date_validite' => Carbon::today(),
            'heure_debut' => $heureDebut,
            'heure_fin' => $heureFin,
            'est_utilise' => false,  // ✅ vrai nom
            'cree_par' => $user->id  // ✅ vrai nom
        ]);

        return [
            'token' => $qrToken,
            'qr_base64' => $this->genererQrBase64($token)
        ];
    }

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
            'qr_base64' => $this->genererQrBase64($token)
        ];
    }

    public function genererQrBase64(string $token): string
    {
        $data = json_encode([
            'token' => $token,
            'type' => 'pointage',
            'timestamp' => now()->timestamp
        ]);

        // ✅ SVG au lieu de PNG
        $qrCode = QrCode::size(300)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($data);

        return 'data:image/svg+xml;base64,' . base64_encode($qrCode);
    }

    public function genererImageQr(string $token): string
    {
        $data = json_encode([
            'token' => $token,
            'type' => 'pointage',
            'timestamp' => now()->timestamp
        ]);

        return QrCode::size(300)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($data);
    }

    public function genererQrPng(string $token): \Illuminate\Http\Response
    {
        $data = json_encode([
            'token' => $token,
            'type' => 'pointage',
            'timestamp' => now()->timestamp
        ]);

        $qrCode = QrCode::size(400)
            ->margin(2)
            ->errorCorrection('H')
            ->generate($data);

        return response($qrCode)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'attachment; filename="qr-pointage-' . date('Y-m-d') . '.svg"');
    }

    public function validerToken(string $token): ?QrToken
    {
        return QrToken::where('token', $token)
            ->whereDate('date_validite', Carbon::today())
            ->where('est_utilise', false)  // ✅ vrai nom
            ->first();
    }

    public function marquerCommeUtilise(QrToken $qrToken): bool
    {
        return $qrToken->update(['est_utilise' => true]);
    }

    public function getQrActifDuJour(User $user): ?QrToken
    {
        return QrToken::where('cree_par', $user->id)  // ✅ vrai nom
            ->whereDate('date_validite', Carbon::today())
            ->where('est_utilise', false)
            ->first();
    }

    public function genererQrPourPeriode(User $user, string $dateDebut, string $dateFin, string $heureDebut = '08:00:00', string $heureFin = '18:00:00'): array
    {
        $debut = Carbon::parse($dateDebut);
        $fin = Carbon::parse($dateFin);
        $qrs = [];

        for ($date = $debut->copy(); $date <= $fin; $date->addDay()) {
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

    public function nettoyerAnciensTokens(): int
    {
        return QrToken::where('date_validite', '<', Carbon::now()->subDays(30))
            ->delete();
    }
}