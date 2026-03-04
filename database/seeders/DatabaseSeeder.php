<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Pointage;
use App\Models\Sanction;
use App\Models\QrToken;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Créer un admin
        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'Super',
            'email' => 'admin@pointage.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'est_actif' => true
        ]);

        // Créer des coaches
        $coach1 = User::create([
            'nom' => 'Martin',
            'prenom' => 'Sophie',
            'email' => 'sophie.martin@pointage.com',
            'password' => Hash::make('password'),
            'role' => 'coach',
            'est_actif' => true
        ]);

        $coach2 = User::create([
            'nom' => 'Dubois',
            'prenom' => 'Thomas',
            'email' => 'thomas.dubois@pointage.com',
            'password' => Hash::make('password'),
            'role' => 'coach',
            'est_actif' => true
        ]);

        // Créer des stagiaires
        $stagiaires = [];
        for ($i = 1; $i <= 10; $i++) {
            $stagiaires[] = User::create([
                'nom' => $this->generateNom(),
                'prenom' => $this->generatePrenom(),
                'email' => "stagiaire{$i}@pointage.com",
                'password' => Hash::make('password'),
                'role' => 'stagiaire',
                'promotion' => 'DEV-' . rand(2023, 2024),
                'date_debut' => now()->subMonths(rand(1, 6)),
                'est_actif' => true
            ]);
        }

        // Affecter des stagiaires aux coaches
        foreach ($stagiaires as $index => $stagiaire) {
            $coach = $index % 2 === 0 ? $coach1 : $coach2;
            $coach->stagiaires()->attach($stagiaire->id, [
                'date_affectation' => now()->subDays(rand(1, 30))
            ]);
        }

        // Créer des pointages pour les 30 derniers jours
        foreach ($stagiaires as $stagiaire) {
            for ($j = 0; $j < 30; $j++) {
                $date = now()->subDays($j);
                
                // 80% de chance d'avoir un pointage
                if (rand(1, 100) <= 80) {
                    $heureArrivee = rand(1, 100) <= 70 
                        ? '08:15:00' 
                        : '08:45:00'; // 30% de retards
                    
                    Pointage::create([
                        'user_id' => $stagiaire->id,
                        'date' => $date,
                        'heure_arrivee' => $heureArrivee,
                        'heure_sortie' => '17:30:00',
                        'statut' => $heureArrivee > '08:30:00' ? 'retard' : 'present',
                        'created_at' => $date,
                        'updated_at' => $date
                    ]);
                } else {
                    // Absent
                    Pointage::create([
                        'user_id' => $stagiaire->id,
                        'date' => $date,
                        'statut' => 'absent',
                        'created_at' => $date,
                        'updated_at' => $date
                    ]);
                }
            }
        }

        // Créer quelques sanctions
        foreach (array_slice($stagiaires, 0, 3) as $stagiaire) {
            Sanction::create([
                'stagiaire_id' => $stagiaire->id,
                'coach_id' => $coach1->id,
                'niveau' => 'avertissement',
                'motif' => 'Retards répétés',
                'description' => '3 retards en une semaine',
                'date_sanction' => now()->subDays(rand(5, 15))
            ]);
        }
    }

    private function generateNom(): string
    {
        $noms = ['Dupont', 'Petit', 'Legrand', 'Moreau', 'Lefebvre', 'Garcia', 'Rodriguez', 'Bernard', 'Thomas', 'Robert'];
        return $noms[array_rand($noms)];
    }

    private function generatePrenom(): string
    {
        $prenoms = ['Jean', 'Marie', 'Pierre', 'Julie', 'Nicolas', 'Isabelle', 'Michel', 'Sylvie', 'Patrick', 'Catherine'];
        return $prenoms[array_rand($prenoms)];
    }
}