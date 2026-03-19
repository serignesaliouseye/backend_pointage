<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pointage;
use App\Models\Sanction;
use Illuminate\Http\Request;

class CoachController extends Controller
{
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

    public function corrigerPointage(Request $request, $pointageId)
    {
        $pointage = Pointage::findOrFail($pointageId);

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

    // ✅ Toutes les sanctions du coach
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

        return response()->json($sanction, 201);
    }

    // ✅ Modifier une sanction
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

    // ✅ Supprimer une sanction
    public function supprimerSanction(Request $request, $sanctionId)
    {
        $sanction = Sanction::where('coach_id', $request->user()->id)
            ->findOrFail($sanctionId);

        $sanction->delete();

        return response()->json(['message' => 'Sanction supprimée avec succès']);
    }

    public function sanctionsDuStagiaire(Request $request, $stagiaireId)
    {
        $coach = $request->user();
        $stagiaire = $coach->stagiaires()->findOrFail($stagiaireId);

        $sanctions = Sanction::where('stagiaire_id', $stagiaire->id)
            ->orderBy('date_sanction', 'desc')
            ->get();

        return response()->json($sanctions);
    }

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