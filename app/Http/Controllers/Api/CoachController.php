<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pointage;
use App\Models\Sanction;
use Illuminate\Http\Request;

class CoachController extends Controller
{
    /**
     * Récupérer la liste des stagiaires du coach connecté
     */
    public function mesStagiaires(Request $request)
    {
        $coach = $request->user();

        $stagiaires = $coach->stagiaires()
            ->with(['pointages' => function($query) {
                $query->whereDate('date', today());
            }])
            ->get()
            ->map(function($stagiaire) {
                $pointageToday = $stagiaire->pointages->first();
                
                return [
                    'id' => $stagiaire->id,
                    'nom' => $stagiaire->nom,
                    'prenom' => $stagiaire->prenom,
                    'email' => $stagiaire->email,
                    'photo' => $stagiaire->photo,
                    'promotion' => $stagiaire->promotion ?? '',
                    'telephone' => $stagiaire->telephone,
                    'date_debut' => $stagiaire->date_debut,
                    'date_fin' => $stagiaire->date_fin,
                    'statut_aujourdhui' => $pointageToday ? $pointageToday->statut : 'absent',
                    'heure_arrivee' => $pointageToday ? $pointageToday->heure_arrivee : null,
                    'total_retards' => Pointage::where('user_id', $stagiaire->id)
                        ->where('statut', 'retard')->count(),
                    'total_absences' => Pointage::where('user_id', $stagiaire->id)
                        ->where('statut', 'absent')->count(),
                    'moyenne_presence' => $this->calculerMoyenne($stagiaire->id),
                ];
            });

        return response()->json($stagiaires);
    }

    /**
     * Récupérer les pointages des stagiaires du coach
     */
    public function mesPointages(Request $request)
    {
        $coach = $request->user();
        $stagiaireIds = $coach->stagiaires()->pluck('users.id');

        $query = Pointage::whereIn('user_id', $stagiaireIds)
            ->with('user')
            ->orderBy('date', 'desc');

        if ($request->date_debut) {
            $query->whereDate('date', '>=', $request->date_debut);
        }
        if ($request->date_fin) {
            $query->whereDate('date', '<=', $request->date_fin);
        }
        if ($request->stagiaire_id) {
            $query->where('user_id', $request->stagiaire_id);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * Récupérer les pointages par date
     */
    public function pointagesParDate(Request $request, $date)
    {
        $coach = $request->user();
        $stagiaireIds = $coach->stagiaires()->pluck('users.id');

        $pointages = Pointage::whereIn('user_id', $stagiaireIds)
            ->whereDate('date', $date)
            ->with('user')
            ->get();

        return response()->json($pointages);
    }

    /**
     * Détail d'un pointage spécifique
     */
    public function detailPointage(Request $request, $pointageId)
    {
        $coach = $request->user();
        $stagiaireIds = $coach->stagiaires()->pluck('users.id');

        $pointage = Pointage::whereIn('user_id', $stagiaireIds)
            ->with('user')
            ->findOrFail($pointageId);

        return response()->json($pointage);
    }

    /**
     * Détail d'un stagiaire spécifique
     */
    public function detailStagiaire(Request $request, $stagiaireId)
    {
        $coach = $request->user();
        $stagiaire = $coach->stagiaires()->findOrFail($stagiaireId);

        $pointages = Pointage::where('user_id', $stagiaireId)
            ->orderBy('date', 'desc')
            ->paginate(20);

        return response()->json([
            'stagiaire' => $stagiaire,
            'pointages' => $pointages
        ]);
    }

    /**
     * Corriger un pointage
     */
    public function corrigerPointage(Request $request, $pointageId)
    {
        $coach = $request->user();
        $stagiaireIds = $coach->stagiaires()->pluck('users.id');

        $pointage = Pointage::whereIn('user_id', $stagiaireIds)
            ->findOrFail($pointageId);

        $request->validate([
            'statut' => 'required|in:present,retard,absent,justifie',
            'heure_arrivee' => 'nullable|date_format:H:i:s',
            'heure_sortie' => 'nullable|date_format:H:i:s',
            'note' => 'nullable|string'
        ]);

        $pointage->update([
            'statut' => $request->statut,
            'heure_arrivee' => $request->heure_arrivee ?? $pointage->heure_arrivee,
            'heure_sortie' => $request->heure_sortie ?? $pointage->heure_sortie,
            'note' => $request->note,
            'corrige_par' => $request->user()->id,
            'corrige_le' => now()
        ]);

        return response()->json($pointage);
    }

    /**
     * Ajouter un justificatif à un pointage
     */
    public function ajouterJustificatif(Request $request, $pointageId)
    {
        $coach = $request->user();
        $stagiaireIds = $coach->stagiaires()->pluck('users.id');

        $pointage = Pointage::whereIn('user_id', $stagiaireIds)
            ->findOrFail($pointageId);

        $request->validate([
            'justificatif' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120'
        ]);

        $path = $request->file('justificatif')->store('justificatifs', 'public');

        $pointage->update([
            'justificatif' => $path,
            'statut' => 'justifie'
        ]);

        return response()->json([
            'message' => 'Justificatif ajouté avec succès',
            'pointage' => $pointage
        ]);
    }

    /**
     * Récupérer toutes les sanctions du coach (pour le dashboard coach)
     */
    public function mesSanctions(Request $request)
    {
        $coach = $request->user();
        $stagiaireIds = $coach->stagiaires()->pluck('users.id');

        $sanctions = Sanction::whereIn('stagiaire_id', $stagiaireIds)
            ->with('stagiaire')
            ->orderBy('date_sanction', 'desc')
            ->get();

        return response()->json($sanctions);
    }

    /**
     * Récupérer les sanctions du stagiaire connecté (pour l'API mobile)
     */
    public function mesSanctionsStagiaire(Request $request)
    {
        $user = $request->user();

        // Vérifier que l'utilisateur est bien un stagiaire
        if ($user->role !== 'stagiaire') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        $sanctions = Sanction::with('coach')
            ->where('stagiaire_id', $user->id)
            ->orderBy('date_sanction', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sanctions
        ]);
    }

    /**
     * Marquer une sanction comme lue (pour le stagiaire)
     */
    public function marquerSanctionCommeLue(Request $request, $sanctionId)
    {
        $user = $request->user();

        // Vérifier que l'utilisateur est un stagiaire
        if ($user->role !== 'stagiaire') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        // Récupérer la sanction du stagiaire
        $sanction = Sanction::where('stagiaire_id', $user->id)
            ->where('id', $sanctionId)
            ->first();

        if (!$sanction) {
            return response()->json([
                'success' => false,
                'message' => 'Sanction non trouvée'
            ], 404);
        }

        // Marquer comme lue
        $sanction->update(['est_lue' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Sanction marquée comme lue'
        ]);
    }

    /**
     * Sanctionner un stagiaire
     */
    public function sanctionner(Request $request)
    {
        $request->validate([
            'stagiaire_id' => 'required|exists:users,id',
            'niveau' => 'required|in:avertissement,blame,suspension,exclusion',
            'motif' => 'required|string',
            'description' => 'required|string',
            'date_fin_suspension' => 'nullable|date|required_if:niveau,suspension'
        ]);

        $coach = $request->user();
        $stagiaire = $coach->stagiaires()->findOrFail($request->stagiaire_id);

        $sanction = Sanction::create([
            'stagiaire_id' => $stagiaire->id,
            'coach_id' => $coach->id,
            'niveau' => $request->niveau,
            'motif' => $request->motif,
            'description' => $request->description,
            'date_sanction' => now(),
            'date_fin_suspension' => $request->date_fin_suspension
        ]);

        // Envoyer une notification au stagiaire (décommentez quand la notification sera prête)
        // $stagiaire->notify(new \App\Notifications\SanctionNotification($sanction));

        return response()->json($sanction, 201);
    }

    /**
     * Modifier une sanction
     */
    public function modifierSanction(Request $request, $sanctionId)
    {
        $sanction = Sanction::where('coach_id', $request->user()->id)
            ->findOrFail($sanctionId);

        $request->validate([
            'niveau' => 'required|in:avertissement,blame,suspension,exclusion',
            'motif' => 'required|string',
            'description' => 'required|string',
            'date_fin_suspension' => 'nullable|date|required_if:niveau,suspension'
        ]);

        $sanction->update($request->only([
            'niveau', 'motif', 'description', 'date_fin_suspension'
        ]));

        return response()->json($sanction);
    }

    /**
     * Supprimer une sanction
     */
    public function supprimerSanction(Request $request, $sanctionId)
    {
        $sanction = Sanction::where('coach_id', $request->user()->id)
            ->findOrFail($sanctionId);

        $sanction->delete();

        return response()->json(['message' => 'Sanction supprimée avec succès']);
    }

    /**
     * Récupérer les sanctions d'un stagiaire spécifique
     */
    public function sanctionsDuStagiaire(Request $request, $stagiaireId)
    {
        $coach = $request->user();
        $stagiaire = $coach->stagiaires()->findOrFail($stagiaireId);

        $sanctions = Sanction::where('stagiaire_id', $stagiaire->id)
            ->orderBy('date_sanction', 'desc')
            ->get();

        return response()->json($sanctions);
    }

    /**
     * Calculer la moyenne de présence d'un stagiaire
     */
    private function calculerMoyenne(int $stagiaireId): float
    {
        $total = Pointage::where('user_id', $stagiaireId)->count();
        if ($total === 0) return 100.0;

        $presents = Pointage::where('user_id', $stagiaireId)
            ->whereIn('statut', ['present', 'retard', 'justifie'])
            ->count();

        return round(($presents / $total) * 100, 1);
    }
}