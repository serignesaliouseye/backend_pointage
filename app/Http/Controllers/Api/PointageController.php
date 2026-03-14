<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pointage;
use App\Models\QrToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PointageController extends Controller
{
    // Scanner QR code (mobile)
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

        // Trouver le token QR valide
        $qrToken = QrToken::where('token', $request->qr_token)
            ->whereDate('date_validite', now())
            ->where('est_utilise', false)
            ->first();

        if (!$qrToken) {
            return response()->json(['message' => 'QR code invalide ou expiré'], 400);
        }

        // Vérifier l'heure
        $heureActuelle = now()->format('H:i:s');
        if ($heureActuelle < $qrToken->heure_debut || $heureActuelle > $qrToken->heure_fin) {
            return response()->json(['message' => 'Hors plage horaire de pointage'], 400);
        }

        // Vérifier si le stagiaire a déjà pointé aujourd'hui
        $pointageExistant = Pointage::where('user_id', $user->id)
            ->whereDate('date', now())
            ->first();

        if ($pointageExistant) {
            if (!$pointageExistant->heure_arrivee) {
                // C'est le départ
                $pointageExistant->update([
                    'heure_sortie' => now()->format('H:i:s'),
                    'statut' => $this->determinerStatut($pointageExistant->heure_arrivee, now()->format('H:i:s'))
                ]);
                $qrToken->update(['est_utilise' => true]);
                
                return response()->json([
                    'message' => 'Départ enregistré',
                    'type' => 'depart',
                    'pointage' => $pointageExistant
                ]);
            } else {
                return response()->json(['message' => 'Vous avez déjà pointé aujourd\'hui'], 400);
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

        $qrToken->update(['est_utilise' => true]);

        return response()->json([
            'message' => 'Arrivée enregistrée',
            'type' => 'arrivee',
            'pointage' => $pointage
        ], 201);
    }

    // Pointage manuel (admin/coach)
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

        return response()->json($pointage);
    }

    // Historique du stagiaire
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

        $pointages = $query->paginate(20);

        return response()->json([
            'data' => $pointages,
            'stats' => [
                'total' => $pointages->total(),
                'present' => $pointages->where('statut', 'present')->count(),
                'retard' => $pointages->where('statut', 'retard')->count(),
                'absent' => $pointages->where('statut', 'absent')->count()
            ]
        ]);
    }

    // Stats dashboard stagiaire
    public function stats(Request $request)
    {
        $user = $request->user();
        $now = now();
        
        $pointagesMois = Pointage::where('user_id', $user->id)
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->get();

        return response()->json([
            'mois_actuel' => [
                'present' => $pointagesMois->where('statut', 'present')->count(),
                'retard' => $pointagesMois->where('statut', 'retard')->count(),
                'absent' => $pointagesMois->where('statut', 'absent')->count(),
                'justifie' => $pointagesMois->where('statut', 'justifie')->count(),
                'total_jours' => $pointagesMois->count()
            ],
            'pourcentage_presence' => $pointagesMois->count() > 0 
                ? round(($pointagesMois->whereIn('statut', ['present', 'retard'])->count() / $pointagesMois->count()) * 100, 2)
                : 0
        ]);
    }

    private function determinerStatut($heureArrivee, $heureSortie = null)
    {
        $heureLimite = '08:30:00'; // Configurable
        
        if ($heureArrivee > $heureLimite) {
            return 'retard';
        }
        
        if ($heureArrivee && !$heureSortie) {
            return 'present';
        }
        
        return 'present';
    }
}