<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pointage;
use App\Models\Sanction;
use Illuminate\Http\Request;

class CoachController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:coach');
    }

    // Liste des stagiaires du coach
    public function mesStagiaires(Request $request)
    {
        $coach = $request->user();
        
        $stagiaires = $coach->stagiaires()
            ->with(['pointages' => function($query) {
                $query->whereDate('date', now());
            }])
            ->get()
            ->map(function($stagiaire) {
                $pointageToday = $stagiaire->pointages->first();
                return [
                    'id' => $stagiaire->id,
                    'nom' => $stagiaire->nom,
                    'prenom' => $stagiaire->prenom,
                    'photo' => $stagiaire->photo,
                    'promotion' => $stagiaire->promotion,
                    'statut_aujourdhui' => $pointageToday ? $pointageToday->statut : 'absent',
                    'heure_arrivee' => $pointageToday ? $pointageToday->heure_arrivee : null,
                    'total_retards' => Pointage::where('user_id', $stagiaire->id)
                        ->where('statut', 'retard')
                        ->count()
                ];
            });

        return response()->json($stagiaires);
    }

    // Détail pointages d'un stagiaire
    public function detailStagiaire(Request $request, $stagiaireId)
    {
        $coach = $request->user();
        
        // Vérifier que le stagiaire appartient bien à ce coach
        $stagiaire = $coach->stagiaires()->findOrFail($stagiaireId);
        
        $pointages = Pointage::where('user_id', $stagiaireId)
            ->with('correcteur')
            ->orderBy('date', 'desc')
            ->paginate(20);

        return response()->json([
            'stagiaire' => $stagiaire,
            'pointages' => $pointages
        ]);
    }

    // Corriger un pointage
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

    // Sanctionner un stagiaire
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
        
        // Vérifier que le stagiaire appartient au coach
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

        // Envoyer notification (à implémenter)
        // $stagiaire->notify(new SanctionNotification($sanction));

        return response()->json($sanction, 201);
    }
}