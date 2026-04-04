<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ Admin
        User::firstOrCreate(
            ['email' => 'admin@pointage.com'],
            [
                'nom' => 'Admin',
                'prenom' => 'Super',
                'password' => Hash::make('Admin@2026!'),
                'role' => 'admin',
                'est_actif' => true,
            ]
        );

        echo "✅ Admin créé ou déjà existant\n";

        // ✅ Coach
        $coach = User::firstOrCreate(
            ['email' => 'coach@pointage.com'],
            [
                'nom' => 'Martin',
                'prenom' => 'Sophie',
                'password' => Hash::make('Coach@2026!'),
                'role' => 'coach',
                'est_actif' => true,
            ]
        );

        echo "✅ Coach créé ou déjà existant\n";

        // ✅ Stagiaire
        $stagiaire = User::firstOrCreate(
            ['email' => 'stagiaire@pointage.com'],
            [
                'nom' => 'Dubois',
                'prenom' => 'Thomas',
                'password' => Hash::make('Stagiaire@2026!'),
                'role' => 'stagiaire',
                'promotion' => 'DEV-2024',
                'date_debut' => now(),
                'est_actif' => true,
            ]
        );

        echo "✅ Stagiaire créé ou déjà existant\n";

        // ✅ Associer le stagiaire au coach si pas déjà fait
        if (!$coach->stagiaires()->where('stagiaire_id', $stagiaire->id)->exists()) {
            $coach->stagiaires()->attach($stagiaire->id);
            echo "✅ Stagiaire associé au coach\n";
        } else {
            echo "✅ Association déjà existante\n";
        }
    }
}