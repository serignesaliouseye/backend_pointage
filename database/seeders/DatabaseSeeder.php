<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Créer un admin
        User::create([
            'nom' => 'Admin',
            'prenom' => 'Super',
            'email' => 'admin@pointage.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'est_actif' => true
        ]);

        // Créer un coach
        $coach = User::create([
            'nom' => 'Martin',
            'prenom' => 'Sophie',
            'email' => 'coach@pointage.com',
            'password' => Hash::make('password'),
            'role' => 'coach',
            'est_actif' => true
        ]);

        // Créer un stagiaire
        $stagiaire = User::create([
            'nom' => 'Dubois',
            'prenom' => 'Thomas',
            'email' => 'stagiaire@pointage.com',
            'password' => Hash::make('password'),
            'role' => 'stagiaire',
            'promotion' => 'DEV-2024',
            'date_debut' => now(),
            'est_actif' => true
        ]);

        // Associer le stagiaire au coach
        $coach->stagiaires()->attach($stagiaire->id);
    }
}