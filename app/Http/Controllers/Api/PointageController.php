<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pointage;
use App\Models\QrToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PointageController extends Controller
{
    /**
     * Scanner QR code (mobile)
     * Le QR code peut être scanné par TOUS les stagiaires
     */
    public function scannerQr(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'qr_token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        
        // Vérifier que l'utilisateur est un stagiaire
        if (!$user->estStagiaire()) {
            return response()->json(['message' => 'Seuls les stagiaires peuvent pointer'], 403);
        }

        // Trouver le token QR valide (sans vérifier est_utilise)
        $qrToken = QrToken::where('token', $request->qr_token)
            ->whereDate('date_validite', now())
            ->first();

        if (!$qrToken) {
            Log::warning('QR code invalide', [
                'token' => $request->qr_token,
                'user_id' => $user->id,
                'date' => now()->toDateString()
            ]);
            return response()->json(['message' => 'QR code invalide ou expiré'], 400);
        }

        // Vérifier l'heure
        $heureActuelle = now()->format('H:i:s');
        if ($heureActuelle < $qrToken->heure_debut || $heureActuelle > $qrToken->heure_fin) {
            return response()->json([
                'message' => 'Hors plage horaire de pointage',
                'plage' => $qrToken->heure_debut . ' - ' . $qrToken->heure_fin
            ], 400);
        }

        // Vérifier si le stagiaire a déjà pointé aujourd'hui
        $pointageExistant = Pointage::where('user_id', $user->id)
            ->whereDate('date', now())
            ->first();

        if ($pointageExistant) {
            // Déjà un pointage aujourd'hui
            if (!$pointageExistant->heure_sortie) {
                // Arrivée déjà enregistrée, c'est le départ
                $pointageExistant->update([
                    'heure_sortie' => now()->format('H:i:s'),
                    'statut' => $this->determinerStatut($pointageExistant->heure_arrivee, now()->format('H:i:s'))
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Départ enregistré avec succès',
                    'type' => 'depart',
                    'pointage' => [
                        'id' => $pointageExistant->id,
                        'date' => $pointageExistant->date,
                        'heure_arrivee' => $pointageExistant->heure_arrivee,
                        'heure_sortie' => $pointageExistant->heure_sortie,
                        'statut' => $pointageExistant->statut
                    ]
                ]);
            } else {
                // Déjà arrivée et départ
                return response()->json([
                    'message' => 'Vous avez déjà pointé arrivée et départ aujourd\'hui'
                ], 400);
            }
        }

        // Nouveau pointage (arrivée)
        $pointage = Pointage::create([
            'user_id' => $user->id,
            'qr_token_id' => $qrToken->id,
            'date' => now(),
            'heure_arrivee' => now()->format('H:i:s'),
            'statut' => $this->determinerStatut(now()->format('H:i:s'))
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Arrivée enregistrée avec succès',
            'type' => 'arrivee',
            'pointage' => [
                'id' => $pointage->id,
                'date' => $pointage->date,
                'heure_arrivee' => $pointage->heure_arrivee,
                'heure_sortie' => $pointage->heure_sortie,
                'statut' => $pointage->statut
            ]
        ], 201);
    }

    /**
     * Pointage manuel (admin/coach)
     */
    public function pointerManuel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'heure_arrivee' => 'required|date_format:H:i:s',
            'heure_sortie' => 'nullable|date_format:H:i:s',
            'note' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pointage = Pointage::updateOrCreate(
            ['user_id' => $request->user_id, 'date' => $request->date],
            [
                'heure_arrivee' => $request->heure_arrivee,
                'heure_sortie' => $request->heure_sortie,
                'note' => $request->note,
                'statut' => $this->determinerStatut($request->heure_arrivee, $request->heure_sortie),
                'corrige_par' => $request->user()->id,
                'corrige_le' => now()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Pointage enregistré/modifié avec succès',
            'data' => $pointage
        ]);
    }

    /**
     * Historique des pointages du stagiaire connecté
     */
    public function historique(Request $request)
    {
        $user = $request->user();
        
        $query = Pointage::where('user_id', $user->id)
            ->with('correcteur')
            ->orderBy('date', 'desc');

        // Filtres optionnels
        if ($request->has('mois')) {
            $query->whereMonth('date', $request->mois);
        }
        if ($request->has('annee')) {
            $query->whereYear('date', $request->annee);
        }
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->has('limit')) {
            $query->limit($request->limit);
        }

        $pointages = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $pointages,
            'stats' => [
                'total' => $pointages->total(),
                'present' => $pointages->where('statut', 'present')->count(),
                'retard' => $pointages->where('statut', 'retard')->count(),
                'absent' => $pointages->where('statut', 'absent')->count(),
                'justifie' => $pointages->where('statut', 'justifie')->count()
            ]
        ]);
    }

    /**
     * Statistiques du stagiaire connecté
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        $now = now();
        
        $pointagesMois = Pointage::where('user_id', $user->id)
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->get();

        $totalJours = $pointagesMois->count();
        $presents = $pointagesMois->whereIn('statut', ['present', 'retard'])->count();
        
        return response()->json([
            'success' => true,
            'mois_actuel' => [
                'present' => $pointagesMois->where('statut', 'present')->count(),
                'retard' => $pointagesMois->where('statut', 'retard')->count(),
                'absent' => $pointagesMois->where('statut', 'absent')->count(),
                'justifie' => $pointagesMois->where('statut', 'justifie')->count(),
                'total_jours' => $totalJours
            ],
            'pourcentage_presence' => $totalJours > 0 
                ? round(($presents / $totalJours) * 100, 2)
                : 0
        ]);
    }

    /**
     * Déterminer le statut en fonction de l'heure d'arrivée
     */
    private function determinerStatut($heureArrivee, $heureSortie = null)
    {
        // Heure limite configurable (08:30:00)
        $heureLimite = '08:30:00';
        
        // Vérifier si c'est un retard
        if ($heureArrivee > $heureLimite) {
            return 'retard';
        }
        
        // Si c'est une arrivée sans départ
        if ($heureArrivee && !$heureSortie) {
            return 'present';
        }
        
        return 'present';
    }

    /**
     * Obtenir le pointage du jour pour le stagiaire
     */
    public function pointageDuJour(Request $request)
    {
        $user = $request->user();
        
        $pointage = Pointage::where('user_id', $user->id)
            ->whereDate('date', now())
            ->first();

        if (!$pointage) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Aucun pointage pour aujourd\'hui'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $pointage->id,
                'date' => $pointage->date,
                'heure_arrivee' => $pointage->heure_arrivee,
                'heure_sortie' => $pointage->heure_sortie,
                'statut' => $pointage->statut,
                'note' => $pointage->note
            ]
        ]);
    }

    /**
     * Vérifier si le stagiaire a déjà pointé aujourd'hui
     */
    public function verifierPointage(Request $request)
    {
        $user = $request->user();
        
        $pointage = Pointage::where('user_id', $user->id)
            ->whereDate('date', now())
            ->first();

        return response()->json([
            'success' => true,
            'a_pointe' => !is_null($pointage),
            'a_arrivee' => !is_null($pointage?->heure_arrivee),
            'a_depart' => !is_null($pointage?->heure_sortie),
            'pointage' => $pointage ? [
                'heure_arrivee' => $pointage->heure_arrivee,
                'heure_sortie' => $pointage->heure_sortie,
                'statut' => $pointage->statut
            ] : null
        ]);
    }
}