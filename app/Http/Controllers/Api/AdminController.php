<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pointage;
use App\Models\QrToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /**
     * Afficher la liste des utilisateurs
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filtres
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nom', 'like', '%' . $request->search . '%')
                  ->orWhere('prenom', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('actif')) {
            $query->where('est_actif', $request->actif === 'true');
        }

        $users = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,coach,stagiaire',
            'telephone' => 'nullable|string|max:20',
            'promotion' => 'nullable|string|max:255',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after:date_debut',
            'est_actif' => 'boolean'
        ]);

        $validated['password'] = Hash::make($validated['password']);
        
        $user = User::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès',
            'data' => $user
        ], 201);
    }

    /**
     * Afficher un utilisateur spécifique
     */
    public function show($id)
    {
        $user = User::with(['pointages' => function ($query) {
            $query->latest()->limit(10);
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|in:admin,coach,stagiaire',
            'telephone' => 'nullable|string|max:20',
            'promotion' => 'nullable|string|max:255',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after:date_debut',
            'est_actif' => 'sometimes|boolean'
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès',
            'data' => $user
        ]);
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Empêcher la suppression de son propre compte
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès'
        ]);
    }

    /**
     * Affecter un stagiaire à un coach
     */
    public function affecterCoach(Request $request, $userId)
    {
        $request->validate([
            'coach_id' => 'required|exists:users,id'
        ]);

        $stagiaire = User::where('id', $userId)
            ->where('role', 'stagiaire')
            ->firstOrFail();

        $coach = User::where('id', $request->coach_id)
            ->where('role', 'coach')
            ->firstOrFail();

        // Vérifier si déjà affecté
        if ($coach->stagiaires()->where('stagiaire_id', $stagiaire->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce stagiaire est déjà affecté à ce coach'
            ], 400);
        }

        $coach->stagiaires()->attach($stagiaire->id, [
            'date_affectation' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stagiaire affecté au coach avec succès',
            'data' => [
                'stagiaire' => $stagiaire,
                'coach' => $coach
            ]
        ]);
    }

    /**
     * Retirer un stagiaire d'un coach
     */
    public function retirerCoach(Request $request, $userId)
    {
        $request->validate([
            'coach_id' => 'required|exists:users,id'
        ]);

        $stagiaire = User::findOrFail($userId);
        $coach = User::findOrFail($request->coach_id);

        $coach->stagiaires()->detach($stagiaire->id);

        return response()->json([
            'success' => true,
            'message' => 'Stagiaire retiré du coach avec succès'
        ]);
    }

    /**
     * Statistiques du dashboard admin
     */
    public function dashboardStats()
    {
        $totalStagiaires = User::where('role', 'stagiaire')->count();
        $totalCoachs = User::where('role', 'coach')->count();
        $totalAdmins = User::where('role', 'admin')->count();

        $aujourdhui = now()->format('Y-m-d');
        $presencesAujourdhui = Pointage::whereDate('date', $aujourdhui)
            ->whereIn('statut', ['present', 'retard'])
            ->count();

        $retardsMois = Pointage::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->where('statut', 'retard')
            ->count();

        $absencesMois = Pointage::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->where('statut', 'absent')
            ->count();

        // Présences par jour (7 derniers jours)
        $presencesHebdo = Pointage::select(
            DB::raw('DATE(date) as date'),
            DB::raw('COUNT(CASE WHEN statut IN ("present", "retard") THEN 1 END) as presents'),
            DB::raw('COUNT(CASE WHEN statut = "absent" THEN 1 END) as absents')
        )
            ->where('date', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'totaux' => [
                    'stagiaires' => $totalStagiaires,
                    'coachs' => $totalCoachs,
                    'admins' => $totalAdmins
                ],
                'aujourdhui' => [
                    'presences' => $presencesAujourdhui,
                    'taux_presence' => $totalStagiaires > 0 
                        ? round(($presencesAujourdhui / $totalStagiaires) * 100, 2)
                        : 0
                ],
                'mois' => [
                    'retards' => $retardsMois,
                    'absences' => $absencesMois
                ],
                'presences_hebdo' => $presencesHebdo
            ]
        ]);
    }

    /**
     * Exporter les pointages
     */
    public function exportPointages(Request $request)
    {
        $request->validate([
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'format' => 'in:csv,excel,pdf'
        ]);

        $pointages = Pointage::with(['user'])
            ->whereBetween('date', [$request->date_debut, $request->date_fin])
            ->orderBy('date')
            ->orderBy('user_id')
            ->get();

        // Format CSV simple
        if ($request->format === 'csv') {
            $csv = "Date,Stagiaire,Email,Arrivée,Départ,Statut,Note\n";
            
            foreach ($pointages as $p) {
                $csv .= implode(',', [
                    $p->date,
                    $p->user->nomComplet,
                    $p->user->email,
                    $p->heure_arrivee ?? '',
                    $p->heure_sortie ?? '',
                    $p->statut,
                    $p->note ?? ''
                ]) . "\n";
            }

            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="pointages.csv"');
        }

        // Format JSON par défaut
        return response()->json([
            'success' => true,
            'data' => $pointages
        ]);
    }

    /**
     * Liste des coachs avec leurs stagiaires
     */
    public function coachsWithStagiaires()
    {
        $coachs = User::where('role', 'coach')
            ->with(['stagiaires' => function ($query) {
                $query->select('users.id', 'nom', 'prenom', 'email', 'promotion');
            }])
            ->get(['id', 'nom', 'prenom', 'email']);

        return response()->json([
            'success' => true,
            'data' => $coachs
        ]);
    }

    /**
     * Activer/Désactiver un utilisateur
     */
    public function toggleActif($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas modifier votre propre statut'
            ], 403);
        }

        $user->update(['est_actif' => !$user->est_actif]);

        return response()->json([
            'success' => true,
            'message' => 'Statut modifié avec succès',
            'data' => ['est_actif' => $user->est_actif]
        ]);
    }
}